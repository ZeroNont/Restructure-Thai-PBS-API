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
        font-size: 14px;
        font-weight: 600;
        line-height: 20px;
    }

    .description {
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
                                        <td align="center" style="padding-top: 10px;">
                                            <img src="https://emeet.thaipbs.or.th/email-images/email-notification-cancel.png"
                                                width="380" height="250" />
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="padding-top: 8px;">
                                <table cellpadding="0" cellspacing="0" width="100%"
                                    style="margin:0 auto; border: 1px solid #DFE6EF; padding: 16px;">
                                    <tr>
                                        <td>
                                            <table cellpadding="0" cellpadding="0" width="100%">
                                                <tr>
                                                    <td style="padding-left: 4px;">
                                                        <span class="headline">{{ $data['subject'] }}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top: 8px;">
                                            <table cellpadding="2" width="100%">
                                                <tr>
                                                    <td width="100">
                                                        <span class="title-row">วันที่ประชุม</span>
                                                    </td>
                                                    <td>
                                                        <span class="title-row"> : </span>
                                                        <span class="danger-description">{{ $data['date_from'] }}</span>
                                                    </td>
                                                </tr>
                                                <tr style="padding-bottom: 8px;">
                                                    <td width="100">
                                                        <span class="title-row">เวลาที่ประชุม</span>
                                                    </td>
                                                    <td>
                                                        <span class="title-row"> : </span>
                                                        <span class="danger-description">{{ $data['time_from'] }}</span>
                                                    </td>
                                                </tr>
                                                <tr style="padding-bottom: 8px;">
                                                    <td width="100">
                                                        <span class="title-row">สถานที่ประชุม</span>
                                                    </td>
                                                    <td>
                                                        <span class="title-row"> : </span>
                                                        <span class="danger-description">{{ $data['address'] }}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table cellpadding="0" cellspacing="0" width="100%" style="margin:0 auto;">
                                    <tr>
                                        <td>
                                            <div class="bottom-line"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table style="margin: 0 auto; padding: 16px 16px 0 16px;" cellpadding="0"
                                                cellspacing="0" width="100%" bgcolor="#F2F4F8">
                                                <tr>
                                                    <td>
                                                        <span
                                                            class="description">หากมีปัญหาหรือข้อสงสัยในการใช้งานระบบ</span>
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
                                                        <span class="description">ติดต่อ</span>
                                                    </td>
                                                    <td>
                                                        <span class="description"> : 1350</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="50">
                                                        <span class="description">E-mail</span>
                                                    </td>
                                                    <td>
                                                        <span class="description"> : IT@thaipbs.or.th</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding-top: 10px;">
                                            <span class="license-description">Copyright © 2022 E-Meeting. All right
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