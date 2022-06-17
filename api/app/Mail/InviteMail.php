<?php

namespace App\Mail;
  
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
  
class InviteMail extends Mailable
{

    use Queueable, SerializesModels;
  
    public $data;
  
    public function __construct($data)
    {
        $this->data = $data;
    }
  
    public function build()
    {
        $subject = ($this->data['permanent']) ? 'เชิญผู้ใช้งานเข้าสู่ระบบ e-Meeting' : 'กรุณายืนยันตัวตนเพื่อเข้าใช้งานระบบ e-Meeting';
        return $this->subject($subject)->view('invite-confirm');
    }

}