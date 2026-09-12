<?php

namespace App\Notifications\Concerns;

trait SendsMailWhenConfigured
{
    /**
     * Luôn gửi database; chỉ thêm kênh mail khi SMTP đã được cấu hình
     * (tránh vỡ luồng ở local/test khi chưa có mail server).
     */
    protected function mailChannels(): array
    {
        $channels = ['database'];

        $mailer = config('mail.default');

        if (! in_array($mailer, ['log', 'array', null], true)
            && filled(config("mail.mailers.{$mailer}.host"))) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
