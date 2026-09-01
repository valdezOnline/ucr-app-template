<!-- Footer Begin -->
{{-- <footer> --}}
<div class="container-fluid footer-container">
    ...
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
