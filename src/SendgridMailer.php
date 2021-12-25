<?php
namespace CarloNicora\Minimalism\Service\SendgridMailer;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use CarloNicora\Minimalism\Interfaces\Mailer\Enums\RecipientType;
use CarloNicora\Minimalism\Interfaces\Mailer\Interfaces\EmailInterface;
use CarloNicora\Minimalism\Interfaces\Mailer\Interfaces\MailerInterface;
use RuntimeException;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\TypeException;

class SendgridMailer extends AbstractService implements MailerInterface
{
    /**
     * @param string $MINIMALISM_SERVICE_MAILER_SENDGRID_API_KEY
     */
    public function __construct(
        private string $MINIMALISM_SERVICE_MAILER_SENDGRID_API_KEY,
    )
    {
        parent::__construct();
    }

    /**
     * @return string|null
     */
    public static function getBaseInterface(
    ): ?string
    {
        return MailerInterface::class;
    }

    /**
     * @param EmailInterface $email
     * @return bool
     * @throws TypeException
     */
    public function send(
        EmailInterface $email,
    ): bool
    {
        $sendGridEmail = new Mail();

        try {
            $sender = $email->getSender();
            $sendGridEmail->setFrom(
                email: $sender->getEmailAddress(),
                name: $sender->getName()??'',
            );
        } catch (TypeException) {
            throw new RuntimeException('Email failed to be sent from the sender', 500);
        }

        foreach ($email->getRecipients() ?? [] as $recipient) {
            switch ($recipient->getType()){
                case RecipientType::To:
                    $sendGridEmail->addTo(
                        to: $recipient->getEmailAddress(),
                        name: $recipient->getName()??'',
                    );
                    break;
                case RecipientType::Cc:
                    $sendGridEmail->addCc(
                        cc: $recipient->getEmailAddress(),
                        name: $recipient->getName()??'',
                    );
                    break;
                case RecipientType::Bcc:
                    $sendGridEmail->addBcc(
                        bcc: $recipient->getEmailAddress(),
                        name: $recipient->getName()??'',
                    );
                    break;
                default:
                    break;
            }
        }

        try {
            $sendGridEmail->setSubject($email->getSubject());
        } catch (TypeException) {
            throw new RuntimeException('Email failed to be sent to recipient', 500);
        }

        $sendGridEmail->addContent($email->getContentType(), $email->getBody());

        $sendgrid = new SendGrid($this->MINIMALISM_SERVICE_MAILER_SENDGRID_API_KEY);

        /** @noinspection UnusedFunctionResultInspection */
        $sendgrid->send($sendGridEmail);

        return true;
    }
}