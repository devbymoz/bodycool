<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;


/**
 * Gère l'envoi des emails
 * 
 */
class EmailService 
{

    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    
    
    /**
     * Permet d'envoyer un mail.
     *
     * @param string $emailRecipient
     * @param string $subject
     * @param array $context
     * @param string $template
     * @return void
     */
    public function sendEmail(
        $emailRecipient,
        $subject, 
        $context, 
        $template,
    ) {
        $sendEmail = new TemplatedEmail();
        $sendEmail->from('BodyCool <noreply@bodycool.com>');
        $sendEmail->to($emailRecipient);
        $sendEmail->replyTo('noreply@bodycool.com');
        $sendEmail->subject($subject);
        $sendEmail->context($context);
        $sendEmail->htmlTemplate($template);
        $this->mailer->send($sendEmail);
    }


}