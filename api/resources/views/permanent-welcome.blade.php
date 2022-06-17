<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style type="text/css">
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap');

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
        margin-top: 16px;
        color: #243248;
        font-size: 24px;
        font-weight: 700;
        line-height: 40px;
    }

    .description {
        font-size: 14px;
        line-height: 24px;
    }

    .danger-description {
        color: #E93152;
        font-size: 14px;
        line-height: 24px;
    }

    .license-description {
        padding-top: 12px;
        color: #707581;
        font-size: 12px;
        line-height: 20px;
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

<body class="body" style="padding:0; margin:0; display:block; background:#E5E5E5; -webkit-text-size-adjust:none;"
    bgcolor="#E5E5E5">

    <table align="center" cellpadding="0" cellspacing="0" width="100%" height="100%">
        <tr>
            <td align="center" valign="top" bgcolor="#E5E5E5" width="100%">
                <center>
                    <table
                        style="margin: 20px auto;  border-top: 4px #FF8652 solid; border-radius: 4px; padding: 16px 22px;"
                        cellpadding="0" cellspacing="0" width="600" bgcolor="#FFFFFF">
                        <tr>
                            <td align="center" valign="top">
                                <table style="margin: 0 auto;" cellpadding="0" cellspacing="0" width="100%"
                                    style="margin:0 auto;">
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
                                            <img src="https://emeet.thaipbs.or.th/email-images/email.png"
                                                width="350" height="250" />
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="padding-top: 16px;">
                                <table style="margin: 0 auto;" cellpadding="0" cellspacing="0" width="100%"
                                    style="margin:0 auto;">
                                    <tr>
                                        <td><span class="headline" style=" margin-top: 16px;  font-size: 24px;font-weight: 700;line-height: 40px;">เรียนคุณ {{ $data['to'] }}</span></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top: 10px;">
                                            <span class="description"
                                                style="margin-top: 10px; font-size: 14px;
                                                line-height: 24px;">ท่านได้ถูกรับเชิญให้เข้าใช้งานระบบการจัดการประชุม
                                                (E-Meeting) ของสถานีโทรทัศน์ไทยพีบีเอส (Thai PBS)
                                                โดยสามารถเข้าใช้งานระบบด้วยข้อมูลรหัสพนักงานและรหัสผ่านของท่าน</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top: 10px;">
                                            <a href="{{ $data['url'] }}"
                                                style="text-align: center !important; align-items: center; background-clip: padding-box; background-color: #fa6400; border: 1px solid transparent; border-radius: 8px; box-shadow: rgba(0, 0, 0, 0.02) 0 1px 3px 0; box-sizing: border-box; color: #fff; cursor: pointer; display: block; font-size: 16px; font-weight: 400; font-family: 'Sarabun', sans-serif; line-height: 1.25; min-height: 3rem; padding: calc(.875rem - 1px) calc(1.5rem - 1px); position: relative; text-decoration: none; transition: ALL 250ms; user-select: none; -webkit-user-select: none; touch-action: manipulation; vertical-align: baseline; width: 200px;"
                                                role="button">เริ่มต้นใช้งาน</a>
                                        </td>
                                    </tr>
                                                                     <tr>
                                        <td>
                                            <div class="bottom-line"></div>
                                        </td>
                                    </tr>
                                    <tr >
                                        <td style="padding-top: 5px;">
                                            <span class="description" style="padding-top: 10px; font-size: 14px;
                                            line-height: 24px;">ขอแสดงความนับถือ
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td >
                                            <span class="description" style="padding-top: 10px; font-size: 14px;
                                            line-height: 24px;">สถานีโทรทัศน์ไทยพีบีเอส (Thai PBS)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top: 10px;">
                                            <table style="margin: 0 auto; padding: 16px 16px 0 16px;" cellpadding="0"
                                                cellspacing="0" width="100%" bgcolor="#F2F4F8">
                                                <tr>
                                                    <td><span
                                                            class="description" style="font-size: 14px;
                                                            line-height: 24px;">หากมีปัญหาหรือข้อสงสัยในการใช้งานระบบ</span>
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
                                                    <td width="50"><span class="description" style="font-size: 14px;
                                                        line-height: 24px;">ติดต่อ</span></td>
                                                    <td><span class="description" style="font-size: 14px;
                                                        line-height: 24px;"> : 1350</span></td>
                                                </tr>
                                                <tr>
                                                    <td width="50"><span class="description">E-mail</span></td>
                                                    <td><span class="description"> : IT@thaipbs.or.th</span></td>
                                                </tr>
                                            </table>
                                        </td>

                                    </tr>
                                    <tr>
                                        <td align="center" style="padding-top: 10px;"><span
                                                class="license-description">Copyright © 2022 E-Meeting. All right
                                                reserved.</span></td>
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
