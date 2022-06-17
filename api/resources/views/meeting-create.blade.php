<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap');

    /* img {
   max-width: 600px;
   outline: none;
   text-decoration: none;
   -ms-interpolation-mode: bicubic;
  } */


    a {
        text-decoration: none;
        border: 0;
        outline: none;
        color: #fff;
        text-align: center !important;
    }

    a img {
        border: none;
    }


    h1,
    h2,
    h3 {
        font-family: 'Sarabun', sans-serif;
        font-weight: 400;
    }

    body {
        -webkit-font-smoothing: antialiased;
        -webkit-text-size-adjust: none;
        /* -webkit-user-select: none;
			-khtml-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			-o-user-select: none;
			user-select: none; */
        font-family: 'Sarabun', sans-serif;
        width: 100%;
        height: 100%;
        color: #37302d;
        background: #ffffff;
        font-size: 16px;
    }

    .title-box {
        margin: 0 auto;
    }

    .image-box {
        margin-top: 12px !important;
        margin: 0 auto;
    }

    .headline {
        /* margin-top: 16px; */
        color: #243248;
        font-size: 20px;
        font-weight: 700;
        line-height: 40px;
    }

    .title-row {
        color: #707581;
        font-size: 14px;
        font-weight: 600;
        line-height: 20px;
    }

    .description {
        font-size: 14px;
        line-height: 24px;
        color: #37302d;
    }

    .position_description {
        font-size: 14px;
        line-height: 24px;
        color: #707581;
    }

    .edit-detail {
        font-size: 14px;
        line-height: 24px;
        font-weight: 600;
        color: #F05225;
    }

    .warning-description {
        color: #F05225;
        font-size: 14px;
        line-height: 24px;
    }

    .danger-description {
        color: #E93152;
        font-size: 14px;
        line-height: 24px;
        padding-left: 8px;
    }

    .license-description {
        padding-top: 12px;
        color: #707581;
        font-size: 12px;
        line-height: 20px;
    }

    .password-text {
        font-size: 24px;
        font-weight: 700;
        line-height: 40px;
        letter-spacing: 6px;
        color: #37302d;
    }

    .force-full-width {
        width: 100% !important;
    }


    .bottom-line {
        margin: 16px 0;
        border: 1px solid #DFE6EF;
    }
    </style>

    <style type="text/css" media="screen">
    @media screen {

        td,
        h1,
        h2,
        h3 {
            font-family: 'Arial', 'sans-serif' !important;
        }
    }
    </style>

    <style type="text/css" media="only screen and (max-width: 480px)">
    @media only screen and (max-width: 480px) {

        table[class="w320"] {
            width: 320px !important;
        }
    }
    </style>

</head>

<body class="body" style="padding:0; margin:0; display:block; background:#F2F4F8; -webkit-text-size-adjust:none;"
    bgcolor="#F2F4F8">
    <table align="center" cellpadding="0" cellspacing="0" width="100%" height="100%">
        <tr>
            <td align="center" valign="top" bgcolor="#F2F4F8" width="100%">
                <center>
                    <table
                        style="margin: 20px auto; border-top: 4px #FF8652 solid; border-radius: 4px; padding: 16px 22px;"
                        cellpadding="0" cellspacing="0" width="600" bgcolor="#FFFFFF">
                        <tr>
                            <td align="center" valign="top">
                                <table cellpadding="0" cellspacing="0" width="100%" style="margin:0 auto;">
                                    <tr>
                                        <td style="text-align:center;">
											<img src="https://emeet.thaipbs.or.th/logo-tpbs.png"
                                                width="50" height="60" />
                                            <img src="https://emeet.thaipbs.or.th/tpbs_title.png"
                                                width="180" height="40" />
                                        </td>
                                    </tr>
                                    <tr>
                                        @if (empty($data['pin'])==false)
                                        <td align="center" style="padding-top: 10px;">
                                            <img src="https://emeet.thaipbs.or.th/email-images/email-notification-join-password.png"
                                                width="380" height="250" />
                                        </td>
                                        @elseif (empty($data['edit_code'])==false)
                                        <td align="center" style="padding-top: 10px;">
                                            <img src="https://emeet.thaipbs.or.th/email-images/email-notification-edit.png"
                                                width="380" height="250" />
                                        </td>
                                        @elseif(empty($data['pin'])&&empty($data['edit_code']))
                                        <td align="center" style="padding-top: 10px;">
                                            <img src="https://emeet.thaipbs.or.th/email-images/email-notification-join.png"
                                                width="380" height="250" />
                                        </td>
                                        @endif
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="padding-top: 8px;">
                                <table cellpadding="0" cellspacing="0" width="100%"
                                    style="margin:0 auto; border: 1px solid #DFE6EF; padding: 16px;">
                                    <tr>
                                        @if (empty($data['pin']))
                                        <td>
                                            <span class="headline"
                                                style=" color: #243248;  font-size: 20px; font-weight: 700; line-height: 40px;">{{ $data['subject'] }}</span>
                                                @if (in_array('SUBJECT', $data['edit_code']))
                                                <span class="edit-detail"
                                                            style="font-size: 14px; line-height: 24px; font-weight: 600; color: #F05225;">(อัปเดต)</span>
                                                @endif
                                            </td>
                                        @else
                                        <td style="padding-left: 4px;">
                                            <span class="headline"
                                                style=" color: #243248;  font-size: 20px; font-weight: 700; line-height: 40px;">ไม่เปิดเผยหัวข้อประชุม</span>
                                        </td>
                                        @endif
                                    </tr>
                                    <tr>
                                        <td style="padding-top: 8px;">
                                            <table cellpadding="2" width="100%">
                                                <tr>
                                                    <td width="100">
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;">วันที่ประชุม</span>
                                                    </td>
                                                    <td>
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;">
                                                            : </span>
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">{{ $data['date_from'] }}</span>
                                                        @if (in_array('DATE', $data['edit_code']))
                                                        <span class="edit-detail"
                                                            style="font-size: 14px; line-height: 24px; font-weight: 600; color: #F05225;">(อัปเดต)</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr style="padding-bottom: 8px;">
                                                    <td width="100">
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;">เวลาที่ประชุม</span>
                                                    </td>
                                                    <td>
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;">
                                                            : </span>
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">{{ $data['time_from'] }}</span>
                                                        @if (in_array('TIME', $data['edit_code']))
                                                        <span class="edit-detail"
                                                            style="font-size: 14px; line-height: 24px; font-weight: 600; color: #F05225;">(อัปเดต)</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr style="padding-bottom: 8px;">
                                                    <td width="100">
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;">สถานที่ประชุม</span>
                                                    </td>
                                                    <td>
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;">
                                                            : </span>
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">{{ $data['address'] }}</span>
                                                        @if (in_array('ADDRESS', $data['edit_code']))
                                                        <span class="edit-detail"
                                                            style="font-size: 14px; line-height: 24px; font-weight: 600; color: #F05225;">(อัปเดต)</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                       <td style="padding-top: 4px;">
                                            <a href="{{ $data['link'] }}"
                                                style="align-items: center; background-clip: padding-box; justify-content:center; background-color: #fa6400; border: 1px solid transparent; border-radius: 8px; box-shadow: rgba(0, 0, 0, 0.02) 0 1px 3px 0; box-sizing: border-box; color: #fff; cursor: pointer; display: inherit; font-size: 16px; font-weight: 400; font-family: 'Sarabun' , sans-serif; justify-content: center; line-height: 1.25; min-height: 3rem; padding: calc(.875rem - 1px) calc(1.5rem - 1px); position: relative; text-decoration: none; transition: ALL 250ms; user-select: none; -webkit-user-select: none; touch-action: manipulation; vertical-align: baseline; width: 200px; text-align:center !important"
                                                role="button">ดูข้อมูลเพิ่มเติม</a>
                                       </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top: 4px;">
                                            <span class="danger-description"
                                                style=" margin-top:10px; color: #E93152;font-size: 14px; line-height: 24px; padding-left: 8px;">
                                                * กรุณาเข้าการเข้าสู่ระบบเพื่อดูข้อมูลการประชุม</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="bottom-line"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table cellpadding="2" cellspacing="0" width="100%">
                                                @foreach ($data['attendee'] as $row)
                                                <tr>
                                                    @if ($loop->first)
                                                    <td width="80">
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;">ผู้ได้รับเชิญ</span>
                                                    </td>
                                                    <td width="10">
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;">
                                                            : </span>
                                                    </td>
                                                    <td width="150">
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">{{ $row['att_full_name'] }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="position_description"
                                                            style=" font-size: 14px; line-height: 24px; color: #707581;">({{ $row['position_name'] }})</span>
                                                    </td>
                                                    @else
                                                    <td width="80">
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;"></span>
                                                    </td>
                                                    <td width="10">
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;"></span>
                                                    </td>
                                                    <td width="150">
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">{{ $row['att_full_name'] }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="position_description"
                                                            style=" font-size: 14px; line-height: 24px; color: #707581;">({{ $row['position_name'] }})</span>
                                                    </td>
                                                    @endif
                                                </tr>
                                                @if ($row['rep_full_name']!=NULL)
                                                <tr>
                                                    <td width="80">
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;"></span>
                                                    </td>
                                                    <td width="10">
                                                        <span class="title-row"
                                                            style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;"></span>
                                                    </td>
                                                    <td width="150">
                                                        <span class="description"
                                                            style="color: #707581; font-size: 14px; line-height: 24px; font-weight: 600;">({{ $row['rep_full_name'] }})</span>
                                                    </td>
                                                </tr>
                                                @endif
                                                @endforeach
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="bottom-line"></div>
                                        </td>
                                    </tr>
                                    @if (empty($data['pin'])==0)
                                    <tr>
                                        <td>
                                            <span class="title-row"
                                                style=" color: #707581; font-size: 14px; font-weight: 600; line-height: 20px;">รหัสการเข้าถึง
                                                :</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table style="margin: 0 auto; padding: 16px; text-align: center;"
                                                cellpadding="0" cellspacing="0" valign="center" width="100%"
                                                bgcolor="#FFF7EA">
                                                <tr>
                                                    <td>
                                                        <span class="password-text"
                                                            style="font-size: 24px; font-weight: 700; line-height: 40px; letter-spacing: 6px; color: #37302d;">{{ $data['pin'] }}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table cellpadding="0" cellspacing="0" width="100%"
                                    style="margin:0 auto; margin-top: 16px;">
                                    <tr>
                                        <td>
                                            <table style="margin: 0 auto; padding: 16px 16px 0 16px;" cellpadding="0"
                                                cellspacing="0" width="100%" bgcolor="#F2F4F8">
                                                <tr>
                                                    <td>
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">หากมีปัญหาหรือข้อสงสัยในการใช้งานระบบ</span>
                                                    </td>
                                                </tr>
                                            </table>

                                 </td>

                                    </tr>
                                    <tr>
                                        <td>
                                            <table style="margin: 0 auto; padding: 0 16px 16px 16px;" cellpadding="0"
                                                cellspacing="0" width="100%" bgcolor="#F2F4F8">
                                                <tr>
                                                    <td width="50">
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">ติดต่อ</span>
                                                    </td>
                                                    <td>
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">
                                                            : 1350</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="50">
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">E-mail</span>
                                                    </td>
                                                    <td>
                                                        <span class="description"
                                                            style="font-size: 14px; line-height: 24px; color: #37302d;">
                                                            : IT@thaipbs.or.th</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding-top: 10px;">

                                            <span class="license-description"
                                                style="font-size: 14px; line-height: 24px; color: #37302d;">Copyright ©
                                                2022 E-Meeting. All right

                                                reserved.</span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </center>
            </td>
        </tr>
    </table>
</body>

</html>
