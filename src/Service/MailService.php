<?php declare(strict_types=1);

namespace App\Service;

use App\Exception\UserException;
use App\Repository\UserRepository;
use \Firebase\JWT\JWT;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Ramsey\Uuid\Uuid;

class MailService
{
    protected $config;

    public function __construct($config)
    {
        LogService::dump($config, "config", __FILE__, __LINE__);
        $this->config = $config;
    }

    public function sendPasswordResetMail($email, $token)
    {
        $domain = $this->config['app_domain'];
        $email_encoded = urlencode($email);

        $url = "$domain/reset-password?t=$token&e=$email_encoded";

        $subject = 'Password reset instructions for Guerizen';
        $content = "Hi, \n
        You have let us know that you've forgotten your password for Guerizen. \n
        Click the following link below to reset your password: \n
        $url \n
        Thanks, \n
        The Guerizen team";

        $this->send_email($email, $subject, $content);
    }

    public function sendVerifyEmailMail($email, $token)
    {
        $domain = $this->config['app_domain'];
        $url = "$domain/verify-email?t=$token";

        $subject = 'Email verification instructions for Guerizen';
        $content = "Hi, \n
        To complete your signup to Guerizen, verify your email by clicking the link below: \n
        $url \n
        Thanks, \n
        The Guerizen team";

        $this->send_email($email, $subject, $content);
    }

    function send_email($to, $subject = '', $content = '')
    {
        LogService::log("to=$to", __FILE__);
        LogService::log("content=$content", __FILE__);

        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码

            $mail->isSMTP();
            //Enable SMTP debugging
            // 0 = off (for production use)
            // 1 = client messages
            // 2 = client and server messages
            $mail->SMTPDebug = 0;
            //调试输出格式
            //$mail->Debugoutput = 'html';
            //smtp服务器
            $mail->Host = $this->config['smtp_server'];
            //端口 - likely to be 25, 465 or 587
            $mail->Port = $this->config['smtp_port'];
            if ($mail->Port === 465) $mail->SMTPSecure = 'ssl';// 使用安全协议
            //Whether to use SMTP authentication
            $mail->SMTPAuth = true;
            //用户名
            $mail->Username = $this->config['smtp_user'];
            //密码
            $mail->Password = $this->config['smtp_pwd'];

            //Set who the message is to be sent from
            $mail->setFrom($this->config['smtp_user']);
            //回复地址
            //$mail->addReplyTo('replyto@example.com', 'First Last');
            //接收邮件方
            if (is_array($to)) {
                foreach ($to as $v) {
                    $mail->addAddress($v);
                }
            } else {
                $mail->addAddress($to);
            }
            //标题
            $mail->Subject = $subject;
            //HTML内容转换
            $mail->msgHTML($content);

            // LogService::dump($mail, "mail");
            //Replace the plain text body with one created manually
            //$mail->AltBody = 'This is a plain-text message body';
            //添加附件
            //$mail->addAttachment('images/phpmailer_mini.png');
            //send the message, check for errors
            $mail->send();
        } catch (Exception $e)
        {
            LogService::log("mail_send failed: " . $e->errorMessage(), __FILE__);
            return false;
        }
        catch (\Exception $e)
        {
             LogService::log("mail_send failed: " . $e->getMessage(), __FILE__);
            return false;
        }

        LogService::log("mail_send success", __FILE__);
        return true;
    }
}
