<?php
if($_SERVER['REQUEST_METHOD']=='POST') { 
$email = $_POST['email'];  

//Save visitor name and entered message into one variable:
$formcontent=" Email: $email\r\n ";
$recipient = "connect@praisepublishinghouse.com";
$subject = "Careers from Praise Publishing House";
$mailheader = "From: $email\r\n";
$mailheader .= "Reply-To: $email\r\n";
$mailheader .= "MIME-Version: 1.0\r\n";
$mail=mail($recipient, $subject, $formcontent, $mailheader);
if($mail)
	{?><script>alert('Thank you for contacting us');
	  window.location.href='free.html';
	</script>

	<?php }
else
{
{?><script>alert('Cannot send mail');
	  window.location.href='free.html';
	</script>

	<?php }

}
}else {
	{?><script>alert('Cannot send mail');
		  window.location.href='free.html';
		</script>
	
		<?php }
	
	}
?>