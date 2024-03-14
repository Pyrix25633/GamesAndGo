<?php
    class Auth {
        static function login(int $id, UserType $type): void {
            $token['id'] = $id;
            $token['type'] = $type->value;
            $expiration = new DateTime();
            $expiration->add(new DateInterval('P' . Settings::AUTH_COOKIE_DURATION));
            $token['expiration'] = $expiration->getTimestamp();
            $json = json_encode($token);
            $cookie = Auth::encrypt($json);
            setcookie(Settings::AUTH_COOKIE_NAME, $cookie, $expiration->getTimestamp(), Settings::URL_ROOT_PATH, "", false, true);
            header('Location: ' . Settings::URL_ROOT_PATH . '/' . $type->value);
        }

        static function protect(array $types): int {
            if(!isset($_COOKIE[Settings::AUTH_COOKIE_NAME])) throw new UnauthorizedResponse();
            $cookie = Auth::decrypt($_COOKIE[Settings::AUTH_COOKIE_NAME]);
            $json = json_decode($cookie, true);
            if(!in_array($json['type'], $types)) throw new ForbiddenResponse();
            if($json['expiration'] < (new DateTime())->getTimestamp()) throw new UnauthorizedResponse();
            return $json['id'];
        }

        static function encrypt(string $plaintext): string {
            $nonce = openssl_random_pseudo_bytes(openssl_cipher_iv_length(Settings::AUTH_COOKIE_METHOD));
            $ciphtertext = openssl_encrypt($plaintext, Settings::AUTH_COOKIE_METHOD, Settings::AUTH_COOKIE_KEY, OPENSSL_RAW_DATA, $nonce);
            return base64_encode($nonce . $ciphtertext);
        }

        static function decrypt(string $encoded): string {
            $decoded = base64_decode($encoded, true);
            if(!$decoded) throw new BadRequestResponse();
            $nonceSize = openssl_cipher_iv_length(Settings::AUTH_COOKIE_METHOD);
            $nonce = mb_substr($decoded, 0, $nonceSize, '8bit');
            $ciphertext = mb_substr($decoded, $nonceSize, null, '8bit');
            $plaintext = openssl_decrypt($ciphertext, Settings::AUTH_COOKIE_METHOD, Settings::AUTH_COOKIE_KEY, OPENSSL_RAW_DATA, $nonce);
            if(!$plaintext) throw new BadRequestResponse();
            return $plaintext;
        }
    }
?>