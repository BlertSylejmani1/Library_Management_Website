<?php

class Mailer
{
    public static function send(string $to, string $subject, string $message, ?string $replyTo = null): array
    {
        $headers = ['MIME-Version: 1.0', 'Content-type: text/plain; charset=UTF-8'];
        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $delivered = false;
        if (function_exists('mail')) {
            $delivered = @mail($to, $subject, $message, implode("\r\n", $headers));
        }

        self::log($to, $subject, $message, $replyTo, $delivered);

        return [
            'sent' => $delivered,
            'logged' => true,
        ];
    }
}