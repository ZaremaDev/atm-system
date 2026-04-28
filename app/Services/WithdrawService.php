<?php

namespace App\Services;

use PDO;
use Exception;

class WithdrawService
{
    private PDO $db;

    // DB baglantisi
	public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ATM-de olan eskinazlari getiririk
	private function getAtmCash($currencyId)
	{
		$stmt = $this->db->prepare("
			SELECT denomination, quantity
			FROM atm_cash
			WHERE currency_id = ?
			ORDER BY denomination DESC
			FOR UPDATE
		");

		$stmt->execute([$currencyId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

    // Meblegi eskinazlara boluruk
	private function makeCashList($amount, $atmCash)
	{
		$cashList = [];
		$leftAmount = $amount;

		foreach ($atmCash as $cash) {
			$denomination = (int)$cash['denomination'];
			$quantity = (int)$cash['quantity'];

			$needCount = intdiv((int)$leftAmount, $denomination);
			$takeCount = min($needCount, $quantity);

			if ($takeCount > 0) {
				$cashList[$denomination] = $takeCount;
				$leftAmount -= $denomination * $takeCount;
			}
		}

		if ($leftAmount != 0) {
			return null;
		}

		return $cashList;
	}
	
	// audit log
	private function writeAuditLog(string $action, $data = [])
	{
		$stmt = $this->db->prepare("
			INSERT INTO audit_logs (action, details)
			VALUES (?, ?)
		");

		$stmt->execute([
			$action,
			json_encode($data, JSON_UNESCAPED_UNICODE)
		]);
	}
	
	// Hesabdan pul chixarma
	public function withdrawMoney(int $accountId, float $amount): array
    {
        $this->db->beginTransaction();

        try {
            if ($amount <= 0) {
                $this->db->rollBack();
				
				$this->writeAuditLog('failed', [
					'account_id' => $accountId,
					'amount' => $amount,
					'reason' => 'Mebleg duzgun deyil'
				]);


                return [
                    'success' => false,
                    'message' => 'Mebleg duzgun deyil'
                ];
            }

            $stmt = $this->db->prepare("
			SELECT a.*, c.code AS currency_code
				FROM accounts a
				JOIN currencies c ON c.id = a.currency_id
				WHERE a.id = ?
				FOR UPDATE");
            $stmt->execute([$accountId]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                $this->db->rollBack();
				
				$this->writeAuditLog('failed', [
					'account_id' => $accountId,
					'amount' => $amount,
					'reason' => 'Hesab tapilmadi'
				]);

                return [
                    'success' => false,
                    'message' => 'Hesab tapilmadi'
                ];
            }

            if ($account['balance'] < $amount) {
                $this->db->rollBack();                
				
				$this->writeAuditLog('failed', [
					'account_id' => $accountId,
					'amount' => $amount,
					'currency' => $account['currency_code'],
					'balance' => $account['balance'],
					'reason' => 'Balans kifayet deyil'
				]);
				
				return [
                    'success' => false,
                    'message' => 'Balans kifayet deyil'
                ];
            }

            $atmCash = $this->getAtmCash((int)$account['currency_id']);

			if (empty($atmCash)) {
                $this->db->rollBack();

				$this->writeAuditLog('failed', [
					'account_id' => $accountId,
					'amount' => $amount,
					'currency' => $account['currency_code'],
					'reason' => 'ATM-de bu valyuta uchun pul yoxdur'
				]);
				
				return [
					'success' => false,
					'message' => 'ATM-de bu valyuta uchun pul yoxdur'
				];
			}

			$cashList = $this->makeCashList($amount, $atmCash);

			if ($cashList === null) {
                $this->db->rollBack();

				 $this->writeAuditLog('failed', [
					'account_id' => $accountId,
					'amount' => $amount,
					'currency' => $account['currency_code'],
					'reason' => 'ATM-de uygun eskinaz kombinasiyasi yoxdur'
				]);
				
				return [
					'success' => false,
					'message' => 'ATM-de uygun eskinaz kombinasiyasi yoxdur'
				];
			}
			
			$newBalance = $account['balance'] - $amount;

            $stmt = $this->db->prepare("UPDATE accounts SET balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $accountId]);

			foreach ($cashList as $denomination => $count) {
				$stmt = $this->db->prepare("
					UPDATE atm_cash
					SET quantity = quantity - ?
					WHERE currency_id = ? AND denomination = ?
				");

				$stmt->execute([
					$count,
					$account['currency_id'],
					$denomination
				]);
			}

            $stmt = $this->db->prepare("
                INSERT INTO transactions (account_id, amount, status)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$accountId, $amount, 'success']);
			
			$this->writeAuditLog('success', [
				'account_id' => $accountId,
				'amount' => $amount,
				'currency' => $account['currency_code'],
				'new_balance' => $newBalance,
				'eskinazlar' => $cashList
			]);

            $this->db->commit();

            return [
                'success' => true,
                'mesaj' => 'Pul chixarildi',
				'valyuta' => $account['currency_code'],
                'yeni_balans' => $newBalance,
				'eskinazlar' => $cashList
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
			
			$this->writeAuditLog('error', [
				'account_id' => $accountId,
				'amount' => $amount,
				'error' => $e->getMessage()
			]);

            return [
                'success' => false,
                'message' => 'Xeta bash verdi',
                'error' => $e->getMessage()
            ];
        }
    }
}