<?php
/**
 * Send_Alert_Mail
 *
 * @package Send_Alert_Mail/Classes
 * @version	0.0.1
 * @since 0.0.3
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Send_Alert_Mail {

	public function __construct() {

	}

	public function mail( $post_id, $post ) {

        $email = get_post_meta( $post_id, 'arb_mail_to_alert', true );
        $domain = get_post_meta( $post_id, 'arb_domain_to_alert', true );
        $expiration = get_post_meta( $post_id, 'arb_domain_expiration', true );

        if ( ! empty( $email ) ) {
        	// emails para quem será enviado o formulário
			$emailenviar = "site@agphost.com.br";
			$destino = $email;
			$assunto = "Contato pelo Site";

			// É necessário indicar que o formato do e-mail é html
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= 'From: $nome <$email>';
			//$headers .= "Bcc: $EmailPadrao\r\n";

			$enviaremail = mail($destino, $assunto, $arquivo, $headers);
			if( $enviaremail ){
				$mgm = "E-MAIL ENVIADO COM SUCESSO! <br> O link será enviado para o e-mail fornecido no formulário";
				echo " <meta http-equiv='refresh' content='10;URL=contato.php'>";
			} else {
				$mgm = "ERRO AO ENVIAR E-MAIL!";
				echo "";
			}
        }

	}

}