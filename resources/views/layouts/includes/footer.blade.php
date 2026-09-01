<!-- Footer Begin -->
{{-- <footer> --}}
<div class="container-fluid footer-container">
    <div class="container">
        <div class="row footer-columns">
            <div class="col footer-column ">
                <section class="footer-item ucr-footer-info">
                    <span class="footer-logo"><span class="show-for-sr">University of
                            California,Riverside</span></span>
                    <address>
                        <p>900 University Ave.<br>
                            Riverside, CA 92521</p>
                        <p>Tel: (951) 827-1012</p>
                    </address>
                    <ul class="footer-links">
                        <li><a href="https://library.ucr.edu/" target="_blank">UCR Library</a></li>
                        <li><a href="https://campusstatus.ucr.edu/" target="_blank">Campus Status</a></li>
                        <li><a href="https://ucr.bncollege.com/shop/ucr/home" target="_blank">Campus Store</a>
                        </li>
                        <li><a href="https://jobs.ucr.edu" target="_blank">Career Opportunities</a></li>
                        <li><a href="https://diversity.ucr.edu/" target="_blank">Diversity</a></li>
                        <li><a href="https://campusmap.ucr.edu/" target="_blank">Maps and Directions</a></li>
                        <li><a href="https://www.ucr.edu/about/visitors" target="_blank">Visit UCR</a></li>
                    </ul>
                </section>
            </div>
            <div class="col footer-column">
                <section id="block-ucriversidefooterdeptinformationblock"
                    data-block-plugin-id="ucr_global_footer_dept_info" class="footer-item footer-dept-info">
                    <h3>University of California, Riverside</h3>
                    <address class="address_2">
                        <p> <a href="https://campusmap.ucr.edu" target="_blank"> 900 University Ave.
                                <br>Riverside, CA 92521 </a></p>
                        <p> tel: (951) 827-1012<br> email: <a href="mailto:library@ucr.edu">library@ucr.edu</a>
                        </p>
                    </address>
                    <p>
                        <a class="" href="https://campusmap.ucr.edu" target="_blank">
                            <i class="fa fa-map-marker-alt" aria-hidden="true"></i>Find Us<br><br>
                            <img src="{{ asset('/images/googlemap.png') }}" alt="View campus map"></a>
                    </p>
                </section>
            </div>
            <div class="col footer-column">
                <section id="block-ucriversiderelatedlinks"
                    data-block-plugin-id="system_menu_block:ucr-related-links-menu"
                    class="footer-item footer-related-links">

                    <h3>Related Links</h3>

                    <ul class="vertical menu">
                        <li><a href="https://library.ucr.edu/collections"
                                data-drupal-link-system-path="node/20">Collections</a>
                        </li>
                    </ul>
                </section>
            </div>
            <div class="col">
                <section class="footer-item">
                    <h3 class="social-title">Follow US:</h3>
                    <div class="social-link">
                        <a href="https://www.facebook.com/UCRiversideLibrary/" target="_blank"
                            class="social-icon-button fa-brands fa-facebook-f" title="Facebook"
                            aria-label="link to facebook"></a>
                        <a href="https://www.youtube.com/ucrlibraries" target="_blank"
                            class="social-icon-button fa-brands fa-youtube" title="YouTube"
                            aria-label="link to youtube"><span class="show-for-sr">YouTube</span></a>
                        <a href="https://www.instagram.com/ucrlibrary/" target="_blank"
                            class="social-icon-button fa-brands fa-instagram" title="Instagram"
                            aria-label="link to instagram"><span class="show-for-sr">Instagram</span></a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
<div class="site-legal-container">
    <div class="container">
        <div class="grid-x grid-padding-x">
            <div class="cell">
                <ul class="site-legal-footer">
                    <!--<li><a href="/feedback" title="Send Feedback E-mail">Feedback</a></li>-->
                    <li>
                        <a href="http://www.ucr.edu/privacy.html" class="ucr-blue-text">Privacy Policy</a>
                    </li>
                    <li>
                        <a href="http://www.ucr.edu/terms.html" class="ucr-blue-text"">Terms and Conditions</a>
                    </li>
                    <li>© <span id=" copyright-year">
                            <script>
                                document.write(new Date().getFullYear());
                            </script>
                        </span> Regents of the University of California
                    </li>
                    <li class="text-muted">
                        <small><i class="fas fa-clock me-1"></i>All times shown in
                            {{ config('app.display_timezone', 'America/Los_Angeles') }}
                            ({{ now(config('app.display_timezone', 'America/Los_Angeles'))->format('T') }})</small>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
{{-- </footer> --}}
<!-- Footer End -->
