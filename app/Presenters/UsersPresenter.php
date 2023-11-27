<?php

namespace App\Presenters;

use Nette;
use Firebase\JWT\JWT; // https://github.com/firebase/php-jwt

/**
 * Prezenter pre pristup k api užívateľov.
 * Posledna zmena(last change): 25.11.2023
 *
 * Modul: API
 *
 * @author Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version 1.0.2
 * @help 1.) https://forum.nette.org/cs/28370-data-z-post-request-body-reactjs-appka-se-po-ceste-do-php-ztrati
 */
class UsersPresenter extends BasePresenter
{

	public function actionDefault(): void
	{
		$this->sendJson($this->user_main->getUsers(true));
	}

	/**
	 * Vráti konkrétneho užívateľa. Ak je id = 0 vráti aktuálne prihláseného užívateľa
	 */
	public function actionUser(int $id = 0): void
	{
		$this->sendJson(
			$this->user_main->getUser(
				($id == 0) ? $this->user->getId() : $id,
				$this->user,
				$this->template->baseUrl,
				true
			)
		);
	}

	public function actionLogIn(): void
	{
		$_post = json_decode(file_get_contents("php://input"), true); // @help 1.)

		try {
			$this->user->login($_post['email'], $_post['password']);

			$privateKey = openssl_pkey_get_private(
				file_get_contents(__DIR__ . '/../../ssl/private_key.pem'),
				$this->config->getPassPhase()
			);

			$user_data = $this->user_main->getUser(
				$this->user->getId(),
				$this->user,
				$this->template->baseUrl,
				true
			);
			// Payload data you want to include in the token
			$payload = [
				'user_id' => $user_data['id'],
				'email' => $user_data['email'],
				'exp' => time() + 7200, // Token expiration time (2 hour)
			];

			// Generate JWT token with private key
			$jwt = JWT::encode($payload, $privateKey, 'RS256');

			// Return the token as JSON response
			$this->sendJson([
				'token' => $jwt,
				'user_data' => $user_data,
			]);

			$this->sendJson(
				$this->user_main->getUser(
					$this->user->getId(),
					$this->user,
					$this->template->baseUrl,
					true
				)
			);
		} catch (Nette\Security\AuthenticationException $e) {
			$this->sendJson(['error' => 'Uživateľské meno alebo heslo je nesprávne!!!']);
		}
	}
}
