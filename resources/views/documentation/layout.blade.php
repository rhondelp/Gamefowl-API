{{--
    Shared shell for the documentation pages (show.blade.php and
    technical.blade.php): <head>, top bar, hero frame, section navigation
    (desktop sidebar + phone chip bar), footer, and the section-highlighting
    script.

    A page provides, from its own @php block:
      $toc        section anchor => [short chip label, full sidebar label]
      $tag        top-bar badge text          (optional, default "Documentation")
      $backUrl    top-bar link target         (optional, default the landing page)
      $backLabel  top-bar link text           (optional, default "Back to home")
    and these sections: title, description, hero, toc-foot, content.
    Page-specific CSS/JS goes in @push('styles') / @push('scripts').
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | GAMEFOWL Documentation</title>
    <meta name="description" content="@yield('description')">
    <meta name="robots" content="noindex, nofollow">

    <!-- Poppins Font (matches the landing and password-reset pages) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @include('documentation.partials.base-styles')
    @stack('styles')
</head>
<body>

    <a class="skip-link" href="#content">Skip to the documentation</a>

    <!-- ══ Top bar ═══════════════════════════════════════════════ -->
    <header class="topbar">
        <div class="container topbar-inner">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">G</span>
                <span class="brand-name">GAMEFOWL</span>
                <span class="brand-tag">{{ $tag ?? 'Documentation' }}</span>
            </a>

            <a class="topbar-link" href="{{ $backUrl ?? route('home') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                {{ $backLabel ?? 'Back to home' }}
            </a>
        </div>
    </header>

    <!-- ══ Hero ══════════════════════════════════════════════════ -->
    <div class="hero">
        <div class="container">
            <div class="hero-inner">
                @yield('hero')
            </div>
        </div>
    </div>

    <!-- ══ Section bar (phones & tablets) ════════════════════════ -->
    <nav class="toc-mobile" aria-label="Sections">
        <ol>
            @foreach ($toc as $id => [$short, $full])
                <li><a href="#{{ $id }}" data-toc="{{ $id }}"><span>{{ $loop->iteration }}</span>{{ $short }}</a></li>
            @endforeach
        </ol>
    </nav>

    <div class="container layout">

        <!-- ══ Sidebar (desktop) ═════════════════════════════════ -->
        <aside class="toc">
            <nav aria-label="Contents">
                <p class="toc-title">On this page</p>
                <ol class="toc-list">
                    @foreach ($toc as $id => [$short, $full])
                        <li><a href="#{{ $id }}" data-toc="{{ $id }}"><span class="toc-num">{{ $loop->iteration }}</span>{{ $full }}</a></li>
                    @endforeach
                </ol>

                <div class="toc-foot">
                    @yield('toc-foot')
                </div>
            </nav>
        </aside>

        <main id="content" class="doc">
            @yield('content')
        </main>
    </div>

    <!-- ══ Footer ════════════════════════════════════════════════ -->
    <footer class="doc-footer">
        <div class="container">
            <p class="foot-brand">GAMEFOWL</p>
            <p class="foot-sub">Expert System for Early Bird Disease Monitoring and Analysis in Gamefowl · Capstone Project</p>
            <p class="foot-links">
                <a href="{{ route('home') }}">Home</a>·<a href="https://github.com/rhondelp/Gamefowl-API" target="_blank" rel="noopener noreferrer">Backend repository</a>
            </p>
            <p class="copyright">&copy; {{ date('Y') }} GAMEFOWL Capstone Project.</p>
        </div>
    </footer>

    <script>
        // Progressive enhancement only: the page reads fine without it.
        (function () {
            // Highlight the section being read in both tables of contents.
            var sections = Array.prototype.slice.call(document.querySelectorAll('.doc-section'));
            var links = Array.prototype.slice.call(document.querySelectorAll('[data-toc]'));
            var chipBar = document.querySelector('.toc-mobile ol');

            function setActive(id) {
                links.forEach(function (link) {
                    var isActive = link.getAttribute('data-toc') === id;

                    link.classList.toggle('is-active', isActive);

                    if (isActive) {
                        link.setAttribute('aria-current', 'location');
                    } else {
                        link.removeAttribute('aria-current');
                    }

                    // Keep the active chip in view without scrolling the page.
                    if (isActive && chipBar && chipBar.contains(link) && chipBar.offsetParent !== null) {
                        chipBar.scrollTo({
                            left: link.offsetLeft - (chipBar.clientWidth - link.offsetWidth) / 2,
                            behavior: 'smooth'
                        });
                    }
                });
            }

            // Section 1 until the reader scrolls into another one.
            if (sections.length) {
                setActive(sections[0].id);
            }

            if ('IntersectionObserver' in window) {
                var visible = {};

                // A section is "current" while it crosses a band near the top
                // of the viewport; the first such section in page order wins.
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        visible[entry.target.id] = entry.isIntersecting;
                    });

                    for (var i = 0; i < sections.length; i++) {
                        if (visible[sections[i].id]) {
                            setActive(sections[i].id);
                            break;
                        }
                    }
                }, { rootMargin: '-15% 0px -75% 0px' });

                sections.forEach(function (section) {
                    observer.observe(section);
                });
            }

            // Print everything, including the collapsed technical notes.
            window.addEventListener('beforeprint', function () {
                document.querySelectorAll('details.tech').forEach(function (details) {
                    details.open = true;
                });
            });
        })();
    </script>
    @stack('scripts')

</body>
</html>
