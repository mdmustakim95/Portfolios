<?php 
//Template Name: Home

get_header(''); 
the_post();

// Handle Form Submission
if(isset($_POST['tax_submit'])) {

    $name    = sanitize_text_field($_POST['name']);
    $email   = sanitize_email($_POST['email']);
    $pps     = sanitize_text_field($_POST['pps']);
    $consent = isset($_POST['consent']) ? 'Yes' : 'No';

    $to = get_option('admin_email');
    $subject = "New Tax Rebate Application";

    $message = "
    Name: $name
    Email: $email
    PPS Number: $pps
    Consent Accepted: $consent
    ";

    $headers = array('Content-Type: text/plain; charset=UTF-8');

    wp_mail($to, $subject, $message, $headers);

    echo '<div class="alert alert-success text-center">Application submitted successfully!</div>';
}
?>

<!-- ================= HERO SECTION ================= -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center">

      <div class="col-lg-7 mb-4">
        <h1>Take The Right Step<br>For Your Business</h1>
        <p class="mt-3">
          Tax Associate is a leading consultancy firm that provides simple,
          effective company registration and accounting solutions in Ireland.
        </p>
        <a href="#" class="btn btn-custom mt-4">Get Started</a>
      </div>

      <div class="col-lg-5">
        <div class="form-card">
          <h5 class="fw-bold mb-4 text-dark">Apply For Tax Rebate Now</h5>

          <form method="POST">

            <input type="text" name="name" class="form-control mb-3" placeholder="Name" required>

            <input type="email" name="email" class="form-control mb-3" placeholder="Email ID" required>

            <input type="text" name="pps" class="form-control mb-3" placeholder="PPS Number" required>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="consent" value="Yes" required>
              <label class="form-check-label small text-dark">
                I have read & agreed to the
                <a href="#" class="text-decoration-none text-info">terms & conditions</a>
              </label>
            </div>

            <button type="submit" name="tax_submit" class="btn btn-custom w-100">
              Apply →
            </button>

          </form>

        </div>
      </div>

    </div>
  </div>
</section>

<!-- ================= SERVICES ================= -->
<section class="services">
  <div class="container">
    <div class="row text-center g-4">

      <div class="col-md-4">
        <div class="service-box">
          <div class="service-icon mx-auto">
            <i class="bi bi-building"></i>
          </div>
          <h5>Start Up Services</h5>
          <p class="text-muted">Company formation and legal services for startups.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="service-box">
          <div class="service-icon mx-auto">
            <i class="bi bi-calculator"></i>
          </div>
          <h5>Finance & Accounting</h5>
          <p class="text-muted">Accounting & financial solutions to scale your business.</p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="service-box">
          <div class="service-icon mx-auto">
            <i class="bi bi-shield-check"></i>
          </div>
          <h5>Risk & Assurance</h5>
          <p class="text-muted">Risk management services for stable business growth.</p>
        </div>
      </div>

    </div>
  </div>
</section>



<?php
  get_footer();
?>