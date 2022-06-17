<?php

namespace App\Mail;
  
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
  
class WelcomeMail extends Mailable
{

    use Queueable, SerializesModels;
  
    public $data;
  
    public function __construct($data)
    {
        $this->data = $data;
    }
  
    public function build()
    {
        return $this->subject('ยินดีต้อนรับเข้าสู่ระบบ Thai PBS e-Meeting')->view('permanent-welcome');
    }

}