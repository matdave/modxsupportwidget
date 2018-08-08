<?php
/**
 * Created with PhpStorm
 * User: matdave
 * Project: modxsupportwidget
 * Date: 8/3/2018
 * https://github.com/matdave
 */
class modxSupportSendProcessor extends modObjectProcessor {
    public $objectType = 'modxsupport.widget';
    public $classKey = 'modxSupportWidget';
    public $permission = 'messages';
    public $languageTopics = array('messages', 'user');


    public $object;

    public $recipients;

    /**
     * {@inheritDoc}
     * @return boolean
     */
    public function initialize() {

        $corePath = $this->modx->getOption('modxsupportwidget.core_path', null, $this->modx->getOption('core_path') . 'components/modxsupportwidget/');
        $this->object = $this->modx->getService('modxsupportwidget', 'modxSupportWidget', $corePath . '/model/modxsupportwidget/', array(
            'core_path' => $corePath
        ));
        $subject = $this->getProperty('fullname');
        if (empty($subject)) {
            return $this->modx->lexicon($this->objectType.'.err_no_fullname');
        }
        $subject = $this->getProperty('email');
        if (empty($subject)) {
            return $this->modx->lexicon($this->objectType.'.err_no_email');
        }
        $subject = $this->getProperty('message');
        if (empty($subject)) {
            return $this->modx->lexicon($this->objectType.'.err_no_message');
        }

        $this->recipients = explode(",",$this->object->getOption('support_email'));

        return parent::initialize();
    }

    public function createMessage() {
        $properties = $this->getProperties();
        $properties['version'] = $this->modx->getVersionData();
        $properties = array_merge($this->object->userArray, $properties);
        $message = $this->object->getFileChunk($this->object->getOption('templatesPath') . 'modxsupportemail.tpl', $properties);
        return $message;
    }

    public function sendMessage($message, $recipient) {
        $this->modx->getService('mail', 'mail.modPHPMailer');
        $this->modx->mail->set(modMail::MAIL_BODY,$message);
        $this->modx->mail->set(modMail::MAIL_FROM,$this->modx->getOption('emailsender', array(), $this->getProperty('email')));
        $this->modx->mail->set(modMail::MAIL_FROM_NAME,$this->modx->getOption('site_name', array(), $this->getProperty('fullname')));
        $this->modx->mail->set(modMail::MAIL_SUBJECT,$this->modx->getOption('site_name'). " " .$this->modx->getOption('site_url') ." Support Request");
        $this->modx->mail->address('to',$recipient);
        $this->modx->mail->setHTML(true);
        if (!$this->modx->mail->send()) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,'An error occurred while trying to send the email: '.$this->modx->mail->mailer->ErrorInfo);
        }
        $this->modx->mail->reset();
    }

    /**
     * {@inheritdoc}
     * @return array|mixed|string
     */
    public function process() {
        foreach ($this->recipients as $recipient) {
            $message = $this->createMessage();
            $sent = $this->sendMessage($message, $recipient);
            if ($sent !== true) {
                return $this->failure($sent, $message);
            }
        }

        return $this->success();
    }
}

return 'modxSupportSendProcessor';
