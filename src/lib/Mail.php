<?php

class Mail {
	static public function send($to, $subject, $body, $is_html=true, $attachments=[]) {
		if (!$body || !$to) return;
		$mail = new \PHPMailer\PHPMailer\PHPMailer();
		$mail->CharSet = 'utf-8';
		$mail->Timeout = 10; // 10 seconds timeout
		$mail->SMTPDebug = \Config::getInstance()->app_debug ? 1 : 0;
		$mail->Debugoutput = function($str, $level) {
			error_log("Mail Debug level $level; message: $str");
		};

		if (\Config::getInstance()->smtp_host) {
			$mail->isSMTP();
			$mail->Host = \Config::getInstance()->smtp_host;
			$mail->Port = intval(\Config::getInstance()->smtp_port);
			$mail->SMTPAutoTLS = true;
			$mail->SMTPKeepAlive = true; // Enable SMTP keep alive
			if (\Config::getInstance()->smtp_port == 465 || \Config::getInstance()->smtp_ssl) {
				$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
			} elseif (\Config::getInstance()->smtp_tls) {
				$mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
			} else {
				$mail->SMTPSecure = '';
			}
			$mail->SMTPOptions = [
				'ssl' => [
					'verify_peer' => false,
				]
			];
			if (\Config::getInstance()->smtp_username) {
				$mail->SMTPAuth = true;
				$mail->Username = \Config::getInstance()->smtp_username;
				$mail->Password = \Config::getInstance()->smtp_password;
				$from = \Config::getInstance()->smtp_username;
				$from_name = \Config::getInstance()->smtp_username;
				$mail->setFrom($from, $from_name);
			}
		}
		$to = explode(',', $to);
		$to = array_map('trim', $to);
		if (is_array($to)) {
			foreach($to as $email) {
				if ($email) $mail->addAddress($email);
			}
		}

		if ($is_html) {
			$mail->isHTML(true);
			$mail->Body = $body;
			$mail->AltBody = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $body));
		} else {
			$mail->Body = $body;
		}

		if ($attachments && is_array($attachments)) {
			foreach($attachments as $attachment) {
				if (is_array($attachment)) {
					if (isset($attachment['path']) && isset($attachment['name'])) {
						$mail->addAttachment($attachment['path'], $attachment['name']);
					} else if (isset($attachment['path'])) {
						$mail->addAttachment($attachment['path']);
					}
				} else if (is_string($attachment)) {
					$mail->addAttachment($attachment);
				}
			}
		}

		$mail->Subject = $subject;

		if (!$mail->send()) {
			return $mail->ErrorInfo;
		}
		return true;
	}
}