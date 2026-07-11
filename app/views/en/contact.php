<?php
$pageTitle = 'SCM | Contact';
$bodyClass = 'contact-body';
$pageCss   = 'contact';
?>
<main class="contact-page page-width">
    <div class="heading">
        <h1>CONTACT US</h1>
    </div>
            <div class="contact-top">
            <div class="contact-box">
                <div class="contact-map">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1278.4678616738897!2d14.082344583382277!3d50.1436342810659!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x470bb7cd8c4a9c9f%3A0x7fdcecadc82d1c9f!2sMiners%20Kladno!5e0!3m2!1scs!2scz!4v1710027633321!5m2!1scs!2scz" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="contact-panel">
                    <?php
                    if(isset($_POST["send"])) {
                        $subject = $_POST["subject"];
                        $email = $_POST["email"];
                        $tel = $_POST["tel"];
                        $name = $_POST["name"];
                        $message = $_POST["message"];
                        $toEmail = "kuceraf@spskladno.cz";
                        
                        // Check if the email is valid
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            ?>
                            <center><div class="alert alert-email">
                            <?php echo "\"$email\" is not a valid email address." ?>
                            </div></center>
                            <?php
                        } elseif (!preg_match("/^[0-9 ]{9,}$/", $tel)) {
                            ?>
                            <center><div class="alert alert-phone">
                            <?php echo "\"$tel\" is not a valid phone number." ?>
                            </div></center>
                            <?php
                        } else {
                            $mailHeaders = "From: $email\r\n";
                            $mailHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";

                            $mailMessage = "
                            <html>
                            <head>
                                <style>
                                    /* Styly pro text ve zprávě */
                                    body {
                                        font-family: 'Poppins', sans-serif; /* Příklad nastavení fontu */
                                        color: #333; /* Příklad nastavení barvy textu */
                                    }
                                    p, ul, img {
                                        margin-left: 15px;
                                        margin-right: 15px;
                                    }
                                    .first-content {
                                        padding-top: 15px;
                                    }
                                    .mailInfo {
                                        padding-bottom: 15px;
                                    }
                                </style>
                            </head>
                            <body>
                                <p class='first-content'>Přišla nová zpráva od uživatele $name</p>
                                <p>Informace:</p>
                                <ul class='mailInfo'>
                                    <li><strong>Jméno:</strong> $name</li>
                                    <li><strong>Email:</strong> $email</li>
                                    <li><strong>Tel.:</strong> $tel</li>
                                    <li><strong>Předmět:</strong> $subject</li>
                                    <li><strong>Zpráva:</strong> $message</li>
                                </ul>
                            </body>
                            </html>
                            ";
                            
                            // Send email only if the email is valid and phone number is valid
                            if (mail($toEmail, $subject, $mailMessage, $mailHeaders)) {
                                ?>
                                <center><div class="alert alert-success">
                                <?php echo "Your mail sent successfully to \"$toEmail\"" ?>
                                </div></center>
                                <?php
                                // Send a copy of the email to the user
                                $toUserSubject = "Copy of your message";
                                $toUserHeaders = "From: no-reply\r\n";
                                $toUserHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";

                                $imageURL = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . asset('images/common/SCM.png'); // logo v potvrzovacim mailu - odkazuje samo na aktualni web

                                $toUserMessage = "
                                <html>
                                <head>
                                    <style>
                                        /* Styly pro text ve zprávě */
                                        body {
                                            font-family: 'Poppins', sans-serif; /* Příklad nastavení fontu */
                                            color: #333; /* Příklad nastavení barvy textu */
                                        }
                                        p, ul, img {
                                            margin-left: 15px;
                                            margin-right: 15px;
                                        }
                                        .first-content {
                                            padding-top: 15px;
                                        }
                                        img {
                                            width: 100%;
                                            max-width: 300px;
                                        }
                                    </style>
                                </head>
                                <body>
                                    <p class='first-content'>Thank you for contacting us!</p>
                                    <p>Your message:</p>
                                    <ul>
                                        <li><strong>Name:</strong> $name</li>
                                        <li><strong>Email:</strong> $email</li>
                                        <li><strong>Tel.:</strong> $tel</li>
                                        <li><strong>Subject:</strong> $subject</li>
                                        <li><strong>Message:</strong> $message</li>
                                    </ul>
                                    <p>We will contact you soon as possible.</p>
                                    <p>note: This message is automatic, please do not reply to this email.<br><p>
                                    <img src='$imageURL' alt='SCM logo'>
                                </body>
                                </html>
                                ";

                                mail($email, $toUserSubject, $toUserMessage, $toUserHeaders);
                            } else {
                                ?>
                                <center><div class="alert alert-failed">
                                <?php echo "Failed while sending your mail!" ?>
                                </div></center>
                                <?php
                            }
                        }
                    }
                    ?>


                    <form class="contact-form" method="post" autocomplete="off">
                    <input type="text" name="name" placeholder="Enter your full name*" class="field" required>
                    <input type="email" name="email" class="field" placeholder="Enter your email*" required>
                    <input type="tel" inputmode="numeric" name="tel" id="tel" class="field" placeholder="Enter your phone*" required>
                    <select class="field" name="subject">
                        <option value="Question">Question</option>
                        <option value="Feedback">Feedback</option>
                        <option value="Complaint">Complaint</option>
                    </select>
                    <textarea placeholder="Message*" name="message" class="field" required></textarea>
                    <input type="submit" value="Send" name="send" class="btn">
                    </form>
                </div>
            </div>
            <div class="contact-methods">
                <div class="contact-method">
                    <i class="fa-solid fa-envelope contact-icon"></i>
                    <article class="contact-text contact-text-email">
                        <h2 class="contact-label">Email</h2>
                        <p class="contact-value"><a href="mailto:scm@minerkladno.cz" target="_blank">scm@minerskladno.cz</a></p>
                    </article>
                </div>
                <div class="contact-method">
                    <i class="fa-solid fa-phone contact-icon"></i>
                    <article class="contact-text contact-text-phone">
                        <h2 class="contact-label">Phone</h2>
                        <p class="contact-value">+420 736 682 453</p>
                    </article>
                </div>
            </div>
            </div>
            <div class="billing-info">
                <h2 class="billing-title">Address and Billing Details</h2>
                <div class="billing-grid">
                    <div class="billing-item">
                        <span class="billing-label">Organisation</span>
                        <span class="billing-data">SPORTOVNÍ CENTRUM MINERS, z.s.</span>
                    </div>
                    <div class="billing-item">
                        <span class="billing-label">Address</span>
                        <span class="billing-data">U Trati 3489, 272 01 Kladno</span>
                    </div>
                    <div class="billing-item">
                        <span class="billing-label">Reg. No. (IČO)</span>
                        <span class="billing-data">08286175</span>
                    </div>
                    <div class="billing-item">
                        <span class="billing-label">Account</span>
                        <span class="billing-data">2901669835/2010</span>
                    </div>
                </div>
            </div>
        </main>

<script>
        const telInput = document.getElementById('tel');

        telInput.addEventListener('input', function(event) {
            const cleaned = telInput.value.replace(/\D/g, '');

            let formatted = '';
            for (let i = 0; i < cleaned.length; i++) {
                if (i > 0 && i % 3 === 0) {
                    formatted += ' ';
                }
                formatted += cleaned[i];
            }

            telInput.value = formatted;
        });
    </script>
