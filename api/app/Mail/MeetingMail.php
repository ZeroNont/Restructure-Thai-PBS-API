<?php

namespace App\Mail;
  
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
  
class MeetingMail extends Mailable
{

    use Queueable, SerializesModels;
  
    public $data;
  
    public function __construct($data)
    {
        $this->data = $data;
    }
  
    public function build()
    {
        $subject = null;
        switch ($this->data['mode_code']) {
            case 'NEW': $subject = 'แจ้งเตือนการเข้าร่วมการประชุมระบบ e-Meeting'; break;
            case 'EDIT': $subject = 'แจ้งเตือนการแก้ไขการประชุมระบบ e-Meeting'; break;
            case 'CANCEL': $subject = 'แจ้งเตือนการยกเลิกการประชุมระบบ e-Meeting'; break;
        }
        $view = (in_array($this->data['mode_code'], ['NEW', 'EDIT'])) ? 'meeting-create' : 'meeting-cancel';
        return $this->subject($subject)->view($view);
    }

}