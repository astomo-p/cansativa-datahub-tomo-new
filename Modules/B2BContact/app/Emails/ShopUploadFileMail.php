<?php

namespace Modules\B2BContact\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShopUploadFileMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $file_url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $data, string $file_url)
    {
        $this->data = $data;
        $this->file_url = $file_url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
      return $this->subject('Neue Kundenliste')
                  ->view('b2bcontact::emails.upload-shop-mail')
                  ->with($this->data)
                  ->attach($this->file_url);
    }
}