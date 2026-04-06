<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: Georgia, 'Times New Roman', serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

                    {{-- Header with gradient --}}
                    <tr>
                        <td style="background: linear-gradient(to right, #1e3a5f, #3B82F6, #1e3a5f); padding: 24px 32px; border-radius: 12px 12px 0 0; text-align: center;">
                            <img src="https://moowaymusicbucket.s3.eu-west-2.amazonaws.com/musicexamshelp/FAVICONmusicexamshelp_logo2+(512+x+512+px)_2.png" alt="musicExams.help" width="48" height="48" style="display: inline-block; border-radius: 12px; margin-bottom: 12px; background-color: #ffffff; padding: 4px;" />
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: bold; font-family: Georgia, 'Times New Roman', serif;">
                                New Contact Form Submission
                            </h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px; border-left: 4px solid #3B82F6; border-right: 4px solid #3B82F6;">

                            {{-- Sender details card --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <span style="color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-family: Arial, sans-serif;">From</span><br />
                                                    <span style="color: #1e3a5f; font-size: 18px; font-weight: bold;">{{ $senderName }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 12px;">
                                                    <span style="color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-family: Arial, sans-serif;">Email</span><br />
                                                    <a href="mailto:{{ $senderEmail }}" style="color: #3B82F6; font-size: 16px; text-decoration: none;">{{ $senderEmail }}</a>
                                                </td>
                                            </tr>
                                            @if($senderSubject)
                                            <tr>
                                                <td>
                                                    <span style="color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; font-family: Arial, sans-serif;">Subject</span><br />
                                                    <span style="color: #1e3a5f; font-size: 16px;">{{ $senderSubject }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Message --}}
                            <h2 style="margin: 0 0 12px 0; color: #1e3a5f; font-size: 16px; font-weight: bold; font-family: Georgia, 'Times New Roman', serif;">Message</h2>
                            <div style="color: #374151; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">{{ $senderMessage }}</div>

                            {{-- Reply button --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 28px;">
                                <tr>
                                    <td align="center">
                                        <a href="mailto:{{ $senderEmail }}?subject=Re: {{ $senderSubject ?? 'Your enquiry to musicExams.help' }}"
                                           style="display: inline-block; background: linear-gradient(to right, #1e3a5f, #3B82F6); color: #ffffff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-size: 16px; font-weight: bold; font-family: Georgia, 'Times New Roman', serif;">
                                            Reply to {{ $senderName }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #000000; padding: 20px 32px; border-radius: 0 0 12px 12px; text-align: center;">
                            <p style="margin: 0; color: #9ca3af; font-size: 13px; font-family: Arial, sans-serif;">
                                This message was sent via the contact form on
                                <a href="https://musicexams.help" style="color: #3B82F6; text-decoration: none;">musicExams.help</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
