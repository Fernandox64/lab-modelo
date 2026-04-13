<?php
$footerCfg = footer_get();
$showStudentCalendarFooter = (bool)($footerCfg['show_calendar'] ?? true);
$studentCalendarFooter = $showStudentCalendarFooter ? ufop_student_calendar() : null;
$footerLinks = (array)($footerCfg['links'] ?? []);
$footerBrandName = trim((string)($footerCfg['brand_name'] ?? SITE_NAME));
$footerBrandSigla = trim((string)($footerCfg['brand_sigla'] ?? SITE_SIGLA));
$footerBrandUniversity = trim((string)($footerCfg['brand_university'] ?? SITE_UNIVERSITY));
$footerContactPhone = trim((string)($footerCfg['contact_phone'] ?? SITE_PHONE));
$footerContactEmail = trim((string)($footerCfg['contact_email'] ?? SITE_EMAIL));
if ($footerBrandName === '') {
    $footerBrandName = SITE_NAME;
}
if ($footerBrandSigla === '') {
    $footerBrandSigla = SITE_SIGLA;
}
if ($footerBrandUniversity === '') {
    $footerBrandUniversity = SITE_UNIVERSITY;
}
if ($footerContactPhone === '') {
    $footerContactPhone = SITE_PHONE;
}
if ($footerContactEmail === '') {
    $footerContactEmail = SITE_EMAIL;
}
?>
<style>
.footer-student-menu{background:linear-gradient(90deg,var(--decom-topbar-bg),var(--decom-topbar-bg-alt))!important;border-top:1px solid rgba(255,255,255,.22);color:#fff}
.footer-links-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem}
.footer-links-grid a{display:block;padding:.35rem .45rem;border-radius:.4rem;text-decoration:none;color:#fff;font-weight:600}
.footer-links-grid a:hover,.footer-links-grid a:focus{background:rgba(255,255,255,.16);color:#fff}
.footer-calendar-card{border:1px solid rgba(84,120,255,.25);border-radius:.7rem;padding:.7rem;background:#f8fbff}
.footer-calendar-title{font-size:.98rem;font-weight:700;color:var(--decom-navy)}
.footer-calendar-mini{font-size:.72rem}
.footer-calendar-mini th,.footer-calendar-mini td{padding:.18rem .08rem!important;text-align:center}
.footer-calendar-mini td.is-empty{background:#f4f6fa}
.footer-calendar-mini td.has-holiday{background:#fff1f2}
.footer-calendar-mini td.has-event{box-shadow:inset 0 -2px 0 rgba(245,158,11,.9)}
@media (max-width:991.98px){.footer-links-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
.scroll-top-progress{--progress:0%;position:fixed;right:1rem;bottom:1rem;width:56px;height:56px;border:0;border-radius:50%;padding:3px;cursor:pointer;z-index:1120;background:conic-gradient(var(--decom-blue) var(--progress),rgba(84,120,255,.2) 0);box-shadow:0 8px 18px rgba(17,31,162,.2);opacity:0;visibility:hidden;transform:translateY(10px);transition:opacity .2s ease,transform .2s ease,visibility .2s ease}
.scroll-top-progress.is-visible{opacity:1;visibility:visible;transform:translateY(0)}
.scroll-top-progress span{display:grid;place-items:center;width:100%;height:100%;border-radius:50%;background:#fff;color:var(--decom-navy);font-size:1.15rem;font-weight:700;line-height:1}
.scroll-top-progress span i{font-size:1.05rem;line-height:1}
.scroll-top-progress:hover span,.scroll-top-progress:focus span{background:var(--decom-cyan);color:var(--decom-navy)}
.site-footer{
    margin-top:0 !important;
    border-top:1px solid rgba(255,255,255,.18) !important;
    background:linear-gradient(90deg,var(--decom-topbar-bg),var(--decom-topbar-bg-alt)) !important;
    color:#fff;
}
.site-footer .text-muted{color:rgba(255,255,255,.82) !important}
</style>
<section class="footer-student-menu py-4">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <div class="footer-links-grid">
                    <?php foreach ($footerLinks as $link): ?>
                        <a href="<?= e((string)$link['url']) ?>"><?= e((string)$link['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="footer-calendar-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="footer-calendar-title">Calendario</div>
                        <?php if ($showStudentCalendarFooter && is_array($studentCalendarFooter)): ?>
                            <a class="btn btn-outline-danger btn-sm py-0 px-2" href="<?= e((string)$studentCalendarFooter['source_url']) ?>" target="_blank" rel="noopener">PROGRAD</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($showStudentCalendarFooter && is_array($studentCalendarFooter)): ?>
                        <div class="small text-muted mb-2"><?= e((string)$studentCalendarFooter['month_name']) ?> <?= e((string)$studentCalendarFooter['year']) ?></div>
                        <div class="table-responsive">
                            <table class="table table-bordered footer-calendar-mini mb-0">
                                <thead>
                                    <tr>
                                        <?php foreach ((array)$studentCalendarFooter['weekdays'] as $wd): ?>
                                            <th><?= e((string)$wd) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $firstDow = (int)$studentCalendarFooter['first_dow'];
                                        $daysInMonth = (int)$studentCalendarFooter['days_in_month'];
                                        $day = 1;
                                        for ($w = 0; $w < 6; $w++):
                                    ?>
                                        <tr>
                                            <?php for ($dow = 0; $dow < 7; $dow++): ?>
                                                <?php if (($w === 0 && $dow < $firstDow) || $day > $daysInMonth): ?>
                                                    <td class="is-empty">&nbsp;</td>
                                                <?php else: ?>
                                                    <?php
                                                        $events = (array)($studentCalendarFooter['days'][$day] ?? []);
                                                        $hasHoliday = false;
                                                        $hasEvent = false;
                                                        foreach ($events as $ev) {
                                                            $t = (string)($ev['type'] ?? '');
                                                            if ($t === 'holiday') {
                                                                $hasHoliday = true;
                                                            } elseif ($t === 'event') {
                                                                $hasEvent = true;
                                                            }
                                                        }
                                                        $class = [];
                                                        if ($hasHoliday) $class[] = 'has-holiday';
                                                        if ($hasEvent) $class[] = 'has-event';
                                                    ?>
                                                    <td class="<?= e(implode(' ', $class)) ?>"><?= e((string)$day) ?></td>
                                                    <?php $day++; ?>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </tr>
                                        <?php if ($day > $daysInMonth) { break; } ?>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="mb-0 text-muted">Calendario desativado no painel admin.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<button id="scrollTopProgress" class="scroll-top-progress" type="button" aria-label="Voltar ao topo">
    <span><i class="bi bi-arrow-up" aria-hidden="true"></i></span>
</button>
<footer class="site-footer py-4"><div class="container d-flex justify-content-between flex-wrap gap-2"><div><strong><?= e($footerBrandName) ?> (<?= e($footerBrandSigla) ?>)</strong><br><span class="text-muted"><?= e($footerBrandUniversity) ?></span></div><div class="text-muted text-end"><?= e($footerContactPhone) ?><br><?= e($footerContactEmail) ?></div></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var btn = document.getElementById('scrollTopProgress');
    if (!btn) return;

    function updateScrollProgress() {
        var top = window.scrollY || document.documentElement.scrollTop || 0;
        var total = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
        var pct = total > 0 ? Math.min(100, Math.max(0, (top / total) * 100)) : 0;
        btn.style.setProperty('--progress', pct + '%');
        btn.setAttribute('aria-label', 'Voltar ao topo (' + Math.round(pct) + '%)');
        if (top > 180) {
            btn.classList.add('is-visible');
        } else {
            btn.classList.remove('is-visible');
        }
    }

    btn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    window.addEventListener('scroll', updateScrollProgress, { passive: true });
    window.addEventListener('resize', updateScrollProgress);
    updateScrollProgress();
})();
</script>
</body></html>
