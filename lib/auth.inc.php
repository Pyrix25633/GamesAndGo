<?php
    const COOKIE_NAME = 'auth-token';
    const DURATION = '14D';
    const KEY = 'a28cvhiierbh1a4';
    const METHOD = 'aes-256-ctr';

    class Auth {
        static function login(int $id, UserType $type): void {
            $token['id'] = $id;
            $token['type'] = $type->value;
            $expiration = new DateTime();
            $expiration->add(new DateInterval('P' . DURATION));
            $token['expiration'] = $expiration->getTimestamp();
            $json = json_encode($token);
            $cookie = Auth::encrypt($json);
            setcookie(COOKIE_NAME, $cookie, $expiration->getTimestamp(), URL_ROOT_PATH, "", false, true);
            header('Location: ' . URL_ROOT_PATH . $type->value);
        }

        static function protect(array $types): int {
            if(!isset($_COOKIE[COOKIE_NAME])) throw new UnauthorizedResponse();
            $cookie = Auth::decrypt($_COOKIE[COOKIE_NAME]);
            $json = json_decode($cookie, true);
            if(!in_array($json['type'], $types)) throw new ForbiddenResponse();
            if($json['expiration'] < (new DateTime())->getTimestamp()) throw new UnauthorizedResponse();
            return $json['id'];
        }

        static function encrypt(string $plaintext): string {
            $nonce = openssl_random_pseudo_bytes(openssl_cipher_iv_length(METHOD));
            $ciphtertext = openssl_encrypt($plaintext, METHOD, KEY, OPENSSL_RAW_DATA, $nonce);
            return base64_encode($nonce . $ciphtertext);
        }

        static function decrypt(string $encoded): string {
            $decoded = base64_decode($encoded, true);
            if(!$decoded) throw new BadRequestResponse();
            $nonceSize = openssl_cipher_iv_length(METHOD);
            $nonce = mb_substr($decoded, 0, $nonceSize, '8bit');
            $ciphertext = mb_substr($decoded, $nonceSize, null, '8bit');
            $plaintext = openssl_decrypt($ciphertext, METHOD, KEY, OPENSSL_RAW_DATA, $nonce);
            if(!$plaintext) throw new BadRequestResponse();
            return $plaintext;
        }
    }
?>