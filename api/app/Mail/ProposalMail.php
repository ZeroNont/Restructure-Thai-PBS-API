<?php

namespace App\Mail;
  
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
  
class ProposalMail extends Mailable
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
        switch ($this->data['mode']) {
            case 'APPROVER': $subject = 'แจ้งเตือนการอนุมัติการเสนอวาระการประชุมระบบ e-Meeting'; break;
            case 'RESULT': $subject = 'แจ้งเตือนผลการเสนอวาระการประชุมระบบ e-Meeting'; break;
        }
        $view = ($this->data['mode'] === 'APPROVER') ? 'notice-meeting-approver' : 'notice-meeting-result';
        return $this->subject($subject)->view($view);
    }

}