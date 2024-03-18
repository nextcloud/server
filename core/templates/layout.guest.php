<!DOCTYPE html>
<html class="ng-csp" data-placeholder-focus="false" lang="<?php p($_['language']); ?>" data-locale="<?php p($_['locale']); ?>" translate="no" >
	<head
<?php if ($_['user_uid']) { ?>
	data-user="<?php p($_['user_uid']); ?>" data-user-displayname="<?php p($_['user_displayname']); ?>"
<?php } ?>
 data-requesttoken="<?php p($_['requesttoken']); ?>">
		<meta charset="utf-8">
		<title>
			<?php
				p(!empty($_['pageTitle']) ? $_['pageTitle'] . ' – ' : '');
p($theme->getTitle());
?>
		</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
		<?php if ($theme->getiTunesAppId() !== '') { ?>
		<meta name="apple-itunes-app" content="app-id=<?php p($theme->getiTunesAppId()); ?>">
		<?php } ?>
		<meta name="theme-color" content="<?php p($theme->getColorPrimary()); ?>">
		<link rel="icon" href="<?php print_unescaped(image_path('core', 'favicon.ico')); /* IE11+ supports png */ ?>">
		<link rel="apple-touch-icon" href="<?php print_unescaped(image_path('core', 'favicon-touch.png')); ?>">
		<link rel="mask-icon" sizes="any" href="<?php print_unescaped(image_path('core', 'favicon-mask.svg')); ?>" color="<?php p($theme->getColorPrimary()); ?>">
		<link rel="manifest" href="<?php print_unescaped(image_path('core', 'manifest.json')); ?>" crossorigin="use-credentials">
		<?php emit_css_loading_tags($_); ?>
		<?php emit_script_loading_tags($_); ?>
		<?php print_unescaped($_['headers']); ?>

      <!-- Custom Head Scaleinfinite Layout-->

			<meta charset="utf-8">
			<meta content="width=device-width, initial-scale=1.0" name="viewport">
			<meta http-equiv="Content-Security-Policy" content="style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;">
			<meta content="" name="description">
			<meta content="" name="keywords">
         <!-- <meta http-equiv="Content-Security-Policy" content="style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;"> -->

			<!-- <meta name="google-signin-client_id" content="740523415320-tll2of0ajhi0qt7nfrnb4kkikfbvsnu8.apps.googleusercontent.com"> -->
			<!-- Favicons -->
			<link href="../assets/img/favicon.png" rel="icon">
			<link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">
			<!-- Google Fonts -->
			<link async href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Montserrat:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>" />
			<!-- <link  href='https://css.gg/userlane.css' rel='stylesheet'> -->
			<!-- font Icons -->
			 <link async rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>" /> 
			<!-- Vendor CSS Files -->
	

			<link async href="../assets/css/style.css" rel="stylesheet">
			<link async href="../assets/css/google-fonts.css" rel="stylesheet">
			<link async href="../assets/vendor/aos/aos.css" rel="stylesheet">
			<link async href="../assets/vendor/optimized/css/bootstrap.min.css" rel="stylesheet">			
			<link async href="../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
			<link async href="../assets/vendor/remixicon/remixicon.css" rel="stylesheet">
			<link async href="../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
     

			<style>
				.loader {
				position: absolute;
				left: 50%;
				top: 50%;
				border: 10px solid #d5d7fd;
				border-radius: 50%;
				border-top: 12px solid #02075d;
				width: 100px;
				height: 100px;
				-webkit-animation: spin 2s linear infinite; /* Safari */
				animation: spin 2s linear infinite;
				}
				/* Safari */
				@-webkit-keyframes spin {
				0% { -webkit-transform: rotate(0deg); }
				100% { -webkit-transform: rotate(360deg); }
				}
				@keyframes spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
				}
			</style>

	</head>
	<body id="<?php p($_['bodyid']);?>">
		<?php include 'layout.noscript.warning.php'; ?>
		<?php foreach ($_['initialStates'] as $app => $initialState) { ?>
			<input type="hidden" id="initial-state-<?php p($app); ?>" value="<?php p(base64_encode($initialState)); ?>">
		<?php }?>

        <?php
            $file = "config/config.php";
            if (file_exists($file) && strpos(file_get_contents($file), " 'installed' => true") == false) {
                // echo "inside if condition";
                ?> 
                 <!-- Next Cloud Layout -->
                 <div class="wrapper">
                    <div class="v-align">
                         <?php if ($_['bodyid'] === 'body-login'): ?>
                          <header>
                             <div id="header">
                                     <div class="logo"></div>
                                </div>
                             </header>
                        <?php endif; ?>
                        <main>
                            <h1 class="hidden-visually">
                                 <?php p($theme->getName()); ?>
                             </h1>
                            <?php print_unescaped($_['content']); ?>
                        </main>
                    </div>
		             </div>
                 <?php
                
             } else { 
                //    echo "inside else condition";
                 ?>
                <!-- Scale Infinite Layout -->
                <div style="background:#fff; width:100%; ">
                    <header id="header" class="header fixed-top">
                    <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
                        <a href="index.php" class="scale-logo d-flex align-items-center">
                        <img src="../assets/img/logo.png" alt="Logo of scaleinfinite">
                        </a> 
                        <nav id="navbar" class="navbar">
                            <ul>
                                <li><a class="nav-link scrollto" href="#contact">Contact</a></li>
                                <li><a class="nav-link scrollto" href="https://docs.scaleinfinite.fr/" target="_blank">Tutorial</a></li>
                                <li>
                                <a class="nav-link scrollto g-signin2" href="apps/sociallogin/oauth/google">
                                <img src="../assets/img/g-signin.png" alt="Sign in with google" style ="width:150px; Height:auto;">
                                </a
                                    >
                                </li>
                                <!-- Google Login -->
                                <li>
                                </li>
                            </ul>
                            <i class="bi bi-list mobile-nav-toggle"></i>
                        </nav>
                        <!-- .navbar -->
                    </div>
                    </header>
                    <!-- End Header -->
            <!-- ======= Hero Section ======= -->
            <section id="hero" class="hero d-flex align-items-center">
               <div class="container">
                  <div class="row">
                     <div class="col-lg-6 d-flex flex-column justify-content-center">
                        <h1 data-aos="fade-up"> Self-Driving Cloud Applications</h1>
                        <h2 data-aos="fade-up" data-aos-delay="400">Put your applications on autopilot mode in our AI managed environment</h2>
                        <div data-aos="fade-up" data-aos-delay="600">
                           <div class="text-center text-lg-start">
                              <a href="#values" class="btn-get-started scrollto d-inline-flex align-items-center justify-content-center align-self-center">
                              <span>Get Started</span>
                              <img src="../assets/img/right-arrow.png" style="margin-left: .8rem;" class="img-fluid" alt="Get Started">
                              </a>
                           </div>
                        </div>
                     </div>
                     <div class="col-lg-6 hero-img" data-aos="zoom-out" data-aos-delay="200">
                        <img src="../assets/img/hero-img.png" class="img-fluid" alt="Hero image">
                     </div>
                  </div>
               </div>
            </section>
            <!-- End Hero -->
            <!-- ======= About Section ======= -->
            <section id="about" class="about">
               <div class="container" data-aos="fade-up">
                  <div class="row gx-0">
                     <div class="col-lg-6 d-flex flex-column justify-content-center" data-aos="fade-up" data-aos-delay="200">
                        <div class="content">
                           <h3>Image Creator</h3>
                           <h2>What is Image Creator</h2>
                           <p>
                              Instantly install Apps. Easily deploy production ready apps. No more tinkering with Dockerfiles and manually provisioning databases.
                           </p>
                           <div class="text-center text-lg-start">
                              <a href="https://docs.scaleinfinite.fr/" target="_blank" class="btn-read-more d-inline-flex align-items-center justify-content-center align-self-center">
                              <span>Get Started</span>
                              <img src="../assets/img/right-arrow.png" style="margin-left: .8rem;" class="img-fluid" alt="Get Started">
                              </a>
                           </div>
                        </div>
                     </div>
                     <div class="col-lg-6 d-flex align-items-center" data-aos="zoom-out" data-aos-delay="200">
                        <img src="../assets/img/my-apps.png" class="img-fluid" alt="App presentation" style="width: 100%;height: 100%;">
                     </div>
                  </div>
               </div>
            </section>
            <!-- End About Section -->
            <!-- ======= Values Section ======= -->
            <section id="values" class="values">
               <div class="container" data-aos="fade-up">
                  <header class="section-header">
                     <h2>Instantly install apps</h2>
                     <p>How it works ?</p>
                  </header>
                  <div class="row">
                     <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="box">
                           <img src="../assets/img/choose.webp" class="img-fluid" alt="Women speaking" style="width: 100%;height: auto;">
                           <h3>Look for the application you are searching for</h3>
                           <p>By using the search bar, you can find +1000 applications from our database & dockerhub</p>
                        </div>
                     </div>
                     <div class="col-lg-4 mt-4 mt-lg-0" data-aos="fade-up" data-aos-delay="400">
                        <div class="box">
                           <img src="../assets/img/install1.webp" class="img-fluid" alt="Women walking" style="width: 100%;height: auto;">
                           <h3>Install application easily</h3>
                           <p>Once you have found the application, you can instantly install the app. Don't forget to specify custom name, ports & environnement variable</p>
                        </div>
                     </div>
                     <div class="col-lg-4 mt-4 mt-lg-0" data-aos="fade-up" data-aos-delay="600">
                        <div class="box">
                           <img src="../assets/img/mindful1.webp" class="img-fluid" alt="Women doing yoga" style="width: 100%;height: auto;">
                           <h3>Ready to use!</h3>
                           <p>You can now use your application peacefully. You can access it with HTTP or TCP/UDP adress.</p>
                        </div>
                     </div>
                  </div>
               </div>
            </section>
            <!-- End Values Section -->
            <!-- ======= Services Section ======= -->
            <section id="services" class="services">
               <div class="container" data-aos="fade-up">
                  <header class="section-header">
                     <h2>Why Use our services ?</h2>
                     <p>Our main objectives</p>
                  </header>
                  <div class="row gy-4">
                     <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="service-box green">
                           <img src="../assets/img/secured.svg" alt="Image of secured" style="max-width: 20%; margin-bottom: 20px;">
                           <h3>Secured</h3>
                           <p>We offer different layer of security options. Users can choose the right security level that suits well for the application as well for their needs.</p>
                        </div>
                     </div>
                     <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="service-box orange">
                           <img src="../assets/img/easy.svg" alt="Image of secured" style="max-width: 13%; margin-bottom: 20px;">
                           <h3>User Friendly</h3>
                           <p>The plateform is designed in a way that anybody from technical and most importantly the non-technical background can easily deploy and run their applications securly</p>
                        </div>
                     </div>
                     <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
                        <div class="service-box blue">
                           <img src="../assets/img/choice.png" alt="Image of thumb" style="width: 25%;height: auto;" >
                           <h3>Choice</h3>
                           <p>Your are not limited to one application from a list of applications that are supported. You choose to deploy and run any application that is publicly available on docker hub and also choose the application from your private docker registry by synchronising your account. </p>
                        </div>
                     </div>
                  </div>
               </div>
            </section>
            <!-- End Services Section -->
            <!-- ======= Counts Section ======= -->
            <section id="counts" class="counts">
               <div class="container" data-aos="fade-up">
                  <div class="row gy-4">
                     <div class="col-lg-3 col-md-6">
                        <div class="count-box">
                           <img src="../assets/img/happy-clients.png" alt="happy Clients" style="height:100px; width:auto; padding-right:20px;">
                           <div>
                              <span data-purecounter-start="0" data-purecounter-end="232" data-purecounter-duration="1" class="purecounter"></span>
                              <p>Happy Clients</p>
                           </div>
                        </div>
                     </div>
                     <div class="col-lg-3 col-md-6">
                        <div class="count-box">
                           <img src="../assets/img/projects.png" alt="Projects" style="height:100px; width:auto; padding-right:20px;">
                           <div>
                              <span data-purecounter-start="0" data-purecounter-end="521" data-purecounter-duration="1" class="purecounter"></span>
                              <p>Projects</p>
                           </div>
                        </div>
                     </div>
                     <div class="col-lg-3 col-md-6">
                        <div class="count-box">
                           <img src="../assets/img/support.png" alt="Support" style="height:100px; width:auto; padding-right:20px;">
                           <div>
                              <span data-purecounter-start="0" data-purecounter-end="1463" data-purecounter-duration="1" class="purecounter"></span>
                              <p>Hours Of Support</p>
                           </div>
                        </div>
                     </div>
                     <div class="col-lg-3 col-md-6">
                        <div class="count-box">
                           <img src="../assets/img/team.png" alt="Team" style="height:100px; width:auto; padding-right:20px;">
                           <div>
                              <span data-purecounter-start="0" data-purecounter-end="15" data-purecounter-duration="1" class="purecounter"></span>
                              <p>Hard Workers</p>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </section>
            <!-- End Counts Section -->
            <!-- ======= F.A.Q Section ======= -->
            <section id="faq" class="faq">
               <div class="container" data-aos="fade-up">
                  <header class="section-header">
                     <h2>F.A.Q</h2>
                     <p>Frequently Asked Questions</p>
                  </header>
                  <div class="row" itemscope itemtype="https://schema.org/FAQPage">
                     <div class="col-lg-6">
                        <!-- F.A.Q List 1-->
                        <div class="accordion accordion-flush" id="faqlist1" >
                           <div class="accordion-item " style="border:none"  style="border:none"  itemscope itemtype="https://schema.org/Question" itemprop="mainEntity">
                              <h2 class="accordion-header">
                                 <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-content-1">
                                 How much does it cost ?
                                 </button>
                              </h2>
                              <div id="faq-content-1" class="accordion-collapse collapse" data-bs-parent="#faqlist1" itemscope itemtype="https://schema.org/Answer" itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer" itemprop="acceptedAnswer">
                                 <div class="accordion-body">
                                    Our service is free. However, applications can be ran for 2 hours only.
                                    Applications can be ran 24/7 with premium user option.
                                 </div>
                              </div>
                           </div>
                           <div class="accordion-item " style="border:none"  itemscope itemtype="https://schema.org/Question" itemprop="mainEntity">
                              <h2 class="accordion-header">
                                 <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-content-2">
                                 How many applications can I install ?
                                 </button>
                              </h2>
                              <div id="faq-content-2" class="accordion-collapse collapse" data-bs-parent="#faqlist1" itemscope itemtype="https://schema.org/Answer" itemprop="acceptedAnswer">
                                 <div class="accordion-body">
                                    For now, you can install unlimited applications with our service.
                                 </div>
                              </div>
                           </div>
                           <div class="accordion-item " style="border:none"  itemscope itemtype="https://schema.org/Question" itemprop="mainEntity">
                              <h2 class="accordion-header">
                                 <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-content-3">
                                 Are my applications endpoint is public?
                                 </button>
                              </h2>
                              <div id="faq-content-3" class="accordion-collapse collapse" data-bs-parent="#faqlist1" itemscope itemtype="https://schema.org/Answer" itemprop="acceptedAnswer">
                                 <div class="accordion-body">
                                    You can choose either public. In that case, you can connect with username & password from anywhere in the internet.
                                    Otherwise if you want to limit the access, you can use vpn connection or limit the IP Adress from where you can connect. 
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="col-lg-6">
                        <!-- F.A.Q List 2-->
                        <div class="accordion accordion-flush" id="faqlist2">
                           <div class="accordion-item " style="border:none"  itemscope itemtype="https://schema.org/Question" itemprop="mainEntity">
                              <h2 class="accordion-header">
                                 <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2-content-1">
                                 Are my data persistent ?
                                 </button>
                              </h2>
                              <div id="faq2-content-1" class="accordion-collapse collapse" data-bs-parent="#faqlist2" itemscope itemtype="https://schema.org/Answer" itemprop="acceptedAnswer">
                                 <div class="accordion-body">
                                    For the free user there is no persistence, and for the premium user you can different type of persistence.
                                 </div>
                              </div>
                              <div class="accordion-item " style="border:none"  itemscope itemtype="https://schema.org/Question" itemprop="mainEntity">
                                 <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2-content-2">
                                    How can I test my applications ?
                                    </button>
                                 </h2>
                                 <div id="faq2-content-2" class="accordion-collapse collapse" data-bs-parent="#faqlist2" itemscope itemtype="https://schema.org/Answer" itemprop="acceptedAnswer">
                                    <div class="accordion-body">
                                       It will only take some few clicks and choose the deployment name/port/variable. And it's done.
                                    </div>
                                 </div>
                              </div>
                              <div class="accordion-item " style="border:none"  itemscope itemtype="https://schema.org/Question" itemprop="mainEntity">
                                 <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2-content-3">
                                    Are my data secured ?
                                    </button>
                                 </h2>
                                 <div id="faq2-content-3" class="accordion-collapse collapse" data-bs-parent="#faqlist2" itemscope itemtype="https://schema.org/Answer" itemprop="acceptedAnswer">
                                    <div class="accordion-body">
                                       Yes they are safe & secured. we provide different security option, you can choose the security option that suits for your needs.
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
            </section>
            <!-- End F.A.Q Section -->
            <!-- ======= Contact Section ======= -->
            <section id="contact" class="contact">
            <div class="container" data-aos="fade-up">
            <header class="section-header">
            <h2>Contact</h2>
            <p>Contact Us</p>
            </header>
            <div class="row gy-4">
            <div class="col-lg-6">
            <div class="row gy-4">
            <div class="col-md-6">
            <div class="info-box">
            <i class="bi bi-geo-alt"></i>
            <h3>Address</h3>
            <p>141 Av. Jean Jaurès,<br> 75019 Paris</p>
            </div>
            </div>
            <div class="col-md-6">
            <div class="info-box">
            <i class="bi bi-clock"></i>
            <h3>Open Hours</h3>
            <p>Monday - Friday<br>9:00AM - 05:00PM</p>
            </div>
            </div>
            <div class="col-md-6">
            <div class="info-box">
            <i class="bi bi-telephone"></i>
            <h3>Call Us</h3>
            <p>+33 9 70 44 00 55</p>
            </div>
            </div>
            <div class="col-md-6">
            <div class="info-box">
            <i class="bi bi-envelope"></i>
            <h3>Email Us</h3>
            <p>info@scaleinfinite.fr</p>
            </div>
            </div>
            </div>
            </div>
            <div class="col-lg-6">
            <form action="forms/contact.php" method="post" class="php-email-form">
            <div class="row gy-4">
            <div class="col-md-12">
            <input type="text" style="background:none; border: 1px solid #ced4da;" name="name" class="form-control" placeholder="Your Name" required>
            </div>
            <div class="col-md-12 ">
            <input type="email" style="background:none; border: 1px solid #ced4da;" class="form-control" name="email" placeholder="Your Email" required>
            </div>
            <div class="col-md-12">
            <input type="text" style="background:none; border: 1px solid #ced4da;" class="form-control" name="subject" placeholder="Subject" required>
            </div>
            <div class="col-md-12">
            <textarea class="form-control" style="background:none; border: 1px solid #ced4da;" name="message" rows="6" placeholder="Message" required></textarea>
            </div>
            <div class="col-md-12 text-center">
            <div class="loading">Loading</div>
            <div class="error-message"></div>
            <div class="sent-message">Your message has been sent. Thank you!</div>
            <button type="submit">Send Message</button>
            </div>
            </div>
            </form>
            </div>
            </div>
            </div>
            </section>
            <!-- End #main -->
            <!-- ======= Footer ======= -->
            <footer id="footer" class="footer">
            <div class="footer-newsletter">
            <div class="container">
            <div class="row justify-content-center">
            <div class="col-lg-12 text-center">
            <h4>Our Newsletter</h4>
            <p>Don't miss any updates by subscribing to our Newsletter</p>
            </div>
            <div class="col-lg-6">
            <form action="" method="post">
            <input type="email" name="email" style="background:none; border: 1px solid #ced4da;"><input type="submit" value="Subscribe">
            </form>
            </div>
            </div>
            </div>
            </div>
            <div class="footer-top">
            <div class="container">
            <div class="row gy-4">
            <div class="col-lg-7 col-md-12 footer-info">
            <a href="index.php" class="scale-logo d-flex align-items-center">
            <img src="../assets/img/logo.png" alt="Image of logo of scaleinfinite" style="width: 20rem;height: 100%;">
            </a>
            <p>Scale Infinite is a data software company based Europe and Asia that develop, educate and offers commercial support for open-source softwares designed to manage Big Data solutions and associated processing.</p>
            <div class="social-links mt-3">
            <a href="https://www.instagram.com/scaleinfinite/" target="_blank" class="instagram"><i class="bi bi-instagram"></i></a>
            <a href="https://www.linkedin.com/company/scaleinfinite" target="_blank" class="linkedin"><i class="bi bi-linkedin"></i></a>
            </div>
            </div>
            <div class="col-lg-3 col-6 footer-links">
            <h4>Useful Links</h4>
            <ul>
            <li><i class="bi bi-chevron-right"></i> <a href="#contact">Contact</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="https://docs.scaleinfinite.fr/" target="_blank">Tutorial</a></li>
            <li><i class="bi bi-chevron-right"></i> <a href="http://localhost/newcloud/index.php/apps/sociallogin/oauth/google">Login</a></li>
            </ul>
            </div>
            <div class="col-lg-2 col-md-12 footer-contact text-center text-md-start">
            <h4>Contact Us</h4>
            <p>
            141 Av. Jean Jaurès,<br>
            75019 Paris<br>
            France <br><br>
            <strong>Phone:</strong> +33 9 70 44 00 55<br>
            <strong>Email:</strong> info@scaleinfinite.fr<br>
            </p>
            </div>
            </div>
            </div>
            </div>
            </footer>
            <!-- End Footer -->
            <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
            <img src="../assets/img/up-arrow.png" alt="scroll-up" style="height:30px; width:auto;">
            </a>
            <!-- Vendor JS Files -->
            <script src="../assets/vendor/purecounter/purecounter.js"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>"></script>
            <script src="../assets/vendor/aos/aos.js"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>"></script>
            <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>"></script>
            <script src="../assets/vendor/glightbox/js/glightbox.min.js"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>"></script>
            <script src="../assets/vendor/isotope-layout/isotope.pkgd.min.js"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>"></script>
            <script src="../assets/vendor/swiper/swiper-bundle.min.js"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>"></script>
            <script src="../assets/vendor/php-email-form/validate.js"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>"></script>
            <!-- Template Main JS File -->
            <script src="../assets/js/mainLandingPage.js"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>"></script>
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>"></script>
            <!-- Loading script -->
            <script  nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>">
               function hideLoader() {
                $('#loading').hide();
               }
                $(window).ready(hideLoader);
               // Strongly recommended: Hide loader after 20 seconds, even if the page hasn't finished loading
               setTimeout(hideLoader, 20 * 1000);
            </script>
                <?php
            }
        ?>

		
		
	</body>
</html>
