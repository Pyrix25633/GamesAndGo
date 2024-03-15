<?php
    class Auth {
        static function login(int $id, UserType $type, string $passwordHash): void {
            $token['id'] = $id;
            $token['passwordHash'] = $passwordHash;
            $expiration = new DateTime();
            $expiration->add(new DateInterval('P' . Settings::AUTH_COOKIE_DURATION));
            $token['expiration'] = $expiration->getTimestamp();
            $json = json_encode($token);
            $cookie = Auth::encrypt($json);
            setcookie(Settings::AUTH_COOKIE_NAME, $cookie, $expiration->getTimestamp(), URL_ROOT_PATH, "", false, true);
            header('Location: ' . URL_ROOT_PATH . '/' . $type->value);
        }

        static function protect(mysqli $connection, array $userTypes): User {
            if(!isset($_COOKIE[Settings::AUTH_COOKIE_NAME])) throw new UnauthorizedResponse();
            $cookie = Auth::decrypt($_COOKIE[Settings::AUTH_COOKIE_NAME]);
            $json = json_decode($cookie, true);
            $id = $json['id'];
            $user = User::select($connection, $id);
            if(!in_array($user->userType->value, $userTypes)) throw new ForbiddenResponse();
            if($json['expiration'] < (new DateTime())->getTimestamp()) throw new UnauthorizedResponse();
            if($json['passwordHash'] != $user->passwordHash);
            return $user;
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