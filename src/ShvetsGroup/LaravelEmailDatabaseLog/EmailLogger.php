<?php

namespace ShvetsGroup\LaravelEmailDatabaseLog;

use DB;
use Illuminate\Mail\Events\MessageSending;

class EmailLogger
{
    /**
     * Handle the event.
     *
     * @param MessageSending $event
     */
    public function handle(MessageSending $event)
    {
        $message = $event->message;

        DB::table('email_log')->insert([
            'date' => date('Y-m-d H:i:s'),
            'from' => $this->formatAddressField($message, 'From'),
            'to' => $this->formatAddressField($message, 'To'),
            'cc' => $this->formatAddressField($message, 'Cc'),
            'bcc' => $this->formatAddressField($message, 'Bcc'),
            'subject' => $message->getSubject(),
            'body' => $message->getBody(),
            'headers' => (string)$message->getHeaders(),
            'attachments' => $this->attachmentFilename($message),
        ]);
    }

    /**
     * Returns all the filenames of the attachements
     * @param $message 
     * @return null|string
     */
    function attachmentFilename($message)
    {
        $filename = null;

        foreach ($message->getChildren() as $child) {
            $disposition = $child->getHeaders()->get('content-disposition');
            if(null !== $disposition)
                $filename .= $disposition->getParameter('filename').PHP_EOL;
        }

        return $filename;
    }

    /**
     * Format address strings for sender, to, cc, bcc.
     *
     * @param $message
     * @param $field
     * @return null|string
     */
    function formatAddressField($message, $field)
    {
        $headers = $message->getHeaders();

        if (!$headers->has($field)) {
            return null;
        }

        $mailboxes = $headers->get($field)->getFieldBodyModel();

        $strings = [];
        foreach ($mailboxes as $email => $name) {
            $mailboxStr = $email;
            if (null !== $name) {
                $mailboxStr = $name . ' <' . $mailboxStr . '>';
            }
            $strings[] = $mailboxStr;
        }
        return implode(', ', $strings);
    }
}