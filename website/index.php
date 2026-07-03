<?php
require_once "../config/db.php";
$s = $conn->query("SELECT * FROM gym_settings WHERE id=1")->fetch_assoc() ?: [];
$gym_name   = htmlspecialchars($s['gym_name'] ?? 'Olympic Fitness Gym');
$logo_path  = htmlspecialchars($s['logo_path'] ?? 'gym logo.jpg');

// Content managed from admin/cashier "Content" tab (falls back to defaults if empty)
$db_trainers = $conn->query("SHOW TABLES LIKE 'website_trainers'")->num_rows
    ? $conn->query("SELECT * FROM website_trainers WHERE is_active = 1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC)
    : [];
$db_gallery  = $conn->query("SHOW TABLES LIKE 'website_gallery'")->num_rows
    ? $conn->query("SELECT * FROM website_gallery WHERE is_active = 1 ORDER BY sort_order, id")->fetch_all(MYSQLI_ASSOC)
    : [];
$address    = htmlspecialchars($s['address'] ?? '');
$phone      = htmlspecialchars($s['phone'] ?? '');
$email_addr = htmlspecialchars($s['email'] ?? '');
$gcash_num  = htmlspecialchars($s['gcash_number'] ?? '+63 XXX XXX XXXX');
$gcash_name = htmlspecialchars($s['gcash_name'] ?? 'Olympic Fitness Gym');
$fb_url     = htmlspecialchars($s['facebook_url'] ?? '#');
$ig_url     = htmlspecialchars($s['instagram_url'] ?? '#');
$hours      = htmlspecialchars($s['hours'] ?? 'Monday – Sunday: 5:00 AM – 10:00 PM');
$about_text = htmlspecialchars($s['about_text'] ?? '');
$about_image = $s['about_image'] ?? '';
$map_embed  = $s['map_embed'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?php echo $gym_name; ?> — Your premier fitness destination. Join today and transform your life with world-class equipment, expert trainers, and flexible membership plans.">
<title><?php echo $gym_name; ?> — Train Hard. Live Strong.</title>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Bebas+Neue&display=swap" rel="stylesheet">
<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ═══════════════════════════════════════════════════════════
   RESET & VARIABLES
═══════════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --gold:    #F5A623;
  --gold-dk: #D4891A;
  --dark:    #0D0D0D;
  --dark2:   #141414;
  --dark3:   #1C1C1C;
  --dark4:   #242424;
  --gray:    #6B7280;
  --gray-lt: #9CA3AF;
  --white:   #FFFFFF;
  --off-wh:  #F9FAFB;
  --red:     #EF4444;
  --green:   #22C55E;
  --radius:  12px;
  --shadow:  0 4px 24px rgba(0,0,0,0.4);
  --trans:   0.25s ease;
  --nav-h:   70px;
}

html { scroll-behavior: smooth; }

body {
  font-family: 'Inter', sans-serif;
  background: var(--dark);
  color: var(--white);
  line-height: 1.6;
  overflow-x: hidden;
}

img { max-width: 100%; display: block; }
a  { text-decoration: none; color: inherit; }

.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.section { padding: 90px 0; }
.section-sm { padding: 60px 0; }

.section-tag {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(245,166,35,0.12);
  border: 1px solid rgba(245,166,35,0.3);
  color: var(--gold);
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 6px 14px;
  border-radius: 20px;
  margin-bottom: 16px;
}

.section-title {
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  font-weight: 800;
  line-height: 1.15;
  margin-bottom: 16px;
}

.section-title span { color: var(--gold); }

.section-sub {
  font-size: 1rem;
  color: var(--gray-lt);
  max-width: 540px;
  line-height: 1.7;
}

.text-center { text-align: center; }
.text-center .section-sub { margin: 0 auto; }

/* ── Buttons ── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: var(--trans);
  letter-spacing: 0.02em;
  white-space: nowrap;
}

.btn-gold {
  background: var(--gold);
  color: var(--dark);
}
.btn-gold:hover {
  background: var(--gold-dk);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(245,166,35,0.35);
}

.btn-outline {
  background: transparent;
  color: var(--white);
  border: 2px solid rgba(255,255,255,0.25);
}
.btn-outline:hover {
  border-color: var(--gold);
  color: var(--gold);
  transform: translateY(-2px);
}

.btn-lg { padding: 16px 36px; font-size: 0.95rem; }
.btn-sm { padding: 10px 20px; font-size: 0.8rem; }

/* ═══════════════════════════════════════════════════════════
   NAVBAR
═══════════════════════════════════════════════════════════ */
#navbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 1000;
  height: var(--nav-h);
  display: flex;
  align-items: center;
  transition: background 0.3s, box-shadow 0.3s;
}

#navbar.scrolled {
  background: rgba(13,13,13,0.97);
  backdrop-filter: blur(20px);
  box-shadow: 0 1px 0 rgba(255,255,255,0.06);
}

.nav-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.nav-logo {
  display: flex;
  align-items: center;
  gap: 12px;
}
.nav-logo img {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--gold);
}
.nav-logo-text {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 1.3rem;
  letter-spacing: 0.05em;
  line-height: 1;
}
.nav-logo-text span { color: var(--gold); }

.nav-links {
  display: flex;
  align-items: center;
  gap: 36px;
  list-style: none;
}
.nav-links a {
  font-size: 0.85rem;
  font-weight: 600;
  color: rgba(255,255,255,0.75);
  letter-spacing: 0.03em;
  transition: color var(--trans);
  position: relative;
}
.nav-links a::after {
  content: '';
  position: absolute;
  bottom: -4px; left: 0; right: 0;
  height: 2px;
  background: var(--gold);
  transform: scaleX(0);
  transition: transform var(--trans);
  transform-origin: left;
}
.nav-links a:hover, .nav-links a.active { color: var(--gold); }
.nav-links a:hover::after, .nav-links a.active::after { transform: scaleX(1); }

.nav-cta { display: flex; align-items: center; gap: 12px; }

/* Hamburger */
.hamburger {
  display: none;
  flex-direction: column;
  gap: 5px;
  cursor: pointer;
  padding: 4px;
  background: none;
  border: none;
}
.hamburger span {
  display: block;
  width: 24px;
  height: 2px;
  background: var(--white);
  border-radius: 2px;
  transition: var(--trans);
}
.hamburger.open span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
.hamburger.open span:nth-child(2) { opacity: 0; }
.hamburger.open span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

/* Mobile menu */
.mobile-menu {
  display: none;
  position: fixed;
  top: var(--nav-h);
  left: 0; right: 0;
  background: rgba(13,13,13,0.98);
  backdrop-filter: blur(20px);
  padding: 24px 20px;
  z-index: 999;
  border-top: 1px solid rgba(255,255,255,0.08);
}
.mobile-menu.open { display: block; }
.mobile-menu ul { list-style: none; }
.mobile-menu li { border-bottom: 1px solid rgba(255,255,255,0.06); }
.mobile-menu a {
  display: block;
  padding: 14px 0;
  font-size: 1rem;
  font-weight: 600;
  color: rgba(255,255,255,0.8);
}
.mobile-menu a:hover { color: var(--gold); }
.mobile-menu .mobile-cta { margin-top: 20px; display: flex; flex-direction: column; gap: 10px; }
.mobile-menu .btn { justify-content: center; }

/* ═══════════════════════════════════════════════════════════
   HERO
═══════════════════════════════════════════════════════════ */
#hero {
  min-height: 100vh;
  display: flex;
  align-items: center;
  position: relative;
  overflow: hidden;
  padding-top: var(--nav-h);
}

.hero-bg {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(135deg, rgba(13,13,13,0.92) 0%, rgba(13,13,13,0.6) 60%, rgba(245,166,35,0.08) 100%),
    url('assets/hero-bg.jpg') center/cover no-repeat;
}

/* Animated grid overlay */
.hero-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(245,166,35,0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(245,166,35,0.04) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
}

.hero-content {
  position: relative;
  z-index: 1;
  max-width: 680px;
}

.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: rgba(245,166,35,0.1);
  border: 1px solid rgba(245,166,35,0.25);
  color: var(--gold);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  padding: 7px 16px;
  border-radius: 20px;
  margin-bottom: 24px;
  animation: fadeInUp 0.6s ease both;
}
.hero-eyebrow .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); animation: blink 1.4s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

.hero-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(3.2rem, 8vw, 6.5rem);
  line-height: 0.95;
  letter-spacing: 0.02em;
  margin-bottom: 24px;
  animation: fadeInUp 0.6s 0.1s ease both;
}
.hero-title .gold { color: var(--gold); }
.hero-title .outline {
  -webkit-text-stroke: 2px rgba(255,255,255,0.6);
  color: transparent;
}

.hero-sub {
  font-size: 1.05rem;
  color: rgba(255,255,255,0.7);
  max-width: 460px;
  margin-bottom: 36px;
  line-height: 1.75;
  animation: fadeInUp 0.6s 0.2s ease both;
}

.hero-btns {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-bottom: 52px;
  animation: fadeInUp 0.6s 0.3s ease both;
}

.hero-stats {
  display: flex;
  gap: 40px;
  animation: fadeInUp 0.6s 0.4s ease both;
}
.hero-stat-num {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 2.4rem;
  color: var(--gold);
  line-height: 1;
}
.hero-stat-label {
  font-size: 0.75rem;
  color: rgba(255,255,255,0.5);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-top: 2px;
}

.hero-scroll {
  position: absolute;
  bottom: 32px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: rgba(255,255,255,0.35);
  font-size: 0.7rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  animation: fadeInUp 0.6s 0.6s ease both;
}
.scroll-line {
  width: 1px;
  height: 50px;
  background: linear-gradient(to bottom, rgba(255,255,255,0.3), transparent);
  animation: scroll-drop 1.8s ease-in-out infinite;
}
@keyframes scroll-drop { 0%{transform:scaleY(0);transform-origin:top} 50%{transform:scaleY(1);transform-origin:top} 51%{transform:scaleY(1);transform-origin:bottom} 100%{transform:scaleY(0);transform-origin:bottom} }

@keyframes fadeInUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:none} }

/* ═══════════════════════════════════════════════════════════
   TICKER / MARQUEE
═══════════════════════════════════════════════════════════ */
.ticker {
  background: var(--gold);
  padding: 12px 0;
  overflow: hidden;
}
.ticker-inner {
  display: flex;
  gap: 0;
  animation: ticker 28s linear infinite;
  white-space: nowrap;
}
.ticker-item {
  display: inline-flex;
  align-items: center;
  gap: 20px;
  padding: 0 40px;
  font-family: 'Bebas Neue', sans-serif;
  font-size: 1rem;
  letter-spacing: 0.08em;
  color: var(--dark);
}
.ticker-item i { font-size: 0.7rem; opacity: 0.5; }
@keyframes ticker { from{transform:translateX(0)} to{transform:translateX(-50%)} }

/* ═══════════════════════════════════════════════════════════
   ABOUT
═══════════════════════════════════════════════════════════ */
#about { background: var(--dark2); }

.about-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 70px;
  align-items: center;
}

.about-img-wrap {
  position: relative;
}
.about-img-main {
  width: 100%;
  height: 500px;
  object-fit: cover;
  border-radius: var(--radius);
  border: 1px solid rgba(255,255,255,0.08);
}
.about-badge {
  position: absolute;
  bottom: -24px;
  right: -24px;
  background: var(--gold);
  color: var(--dark);
  border-radius: var(--radius);
  padding: 20px 24px;
  text-align: center;
  box-shadow: var(--shadow);
}
.about-badge-num {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 2.6rem;
  line-height: 1;
}
.about-badge-text { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; }

.about-features {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-top: 32px;
}
.about-feat {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: var(--dark3);
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.06);
}
.about-feat-icon {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: rgba(245,166,35,0.12);
  color: var(--gold);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}
.about-feat-title { font-size: 0.82rem; font-weight: 700; margin-bottom: 2px; }
.about-feat-sub   { font-size: 0.72rem; color: var(--gray-lt); }

/* ═══════════════════════════════════════════════════════════
   PROGRAMS / MEMBERSHIPS
═══════════════════════════════════════════════════════════ */
#programs { background: var(--dark); }

.programs-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-top: 56px;
}

.program-card {
  background: var(--dark3);
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.07);
  transition: transform var(--trans), border-color var(--trans), box-shadow var(--trans);
  position: relative;
}
.program-card:hover {
  transform: translateY(-6px);
  border-color: rgba(245,166,35,0.4);
  box-shadow: 0 20px 48px rgba(0,0,0,0.5);
}
.program-card.featured {
  border-color: var(--gold);
  background: linear-gradient(145deg, var(--dark3), rgba(245,166,35,0.05));
}
.program-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  background: var(--gold);
  color: var(--dark);
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 20px;
}

.program-img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  background: var(--dark4);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,0.15);
  font-size: 3rem;
}

.program-body { padding: 24px; }
.program-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(245,166,35,0.12);
  color: var(--gold);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  margin-bottom: 16px;
}
.program-name { font-size: 1.15rem; font-weight: 800; margin-bottom: 8px; }
.program-desc { font-size: 0.82rem; color: var(--gray-lt); line-height: 1.65; margin-bottom: 20px; }

.program-price-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  margin-bottom: 20px;
}
.program-price {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 2.2rem;
  color: var(--gold);
  line-height: 1;
}
.program-price-period { font-size: 0.78rem; color: var(--gray-lt); padding-bottom: 4px; }

.program-features { list-style: none; margin-bottom: 24px; }
.program-features li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.82rem;
  color: rgba(255,255,255,0.75);
  padding: 5px 0;
}
.program-features li i { color: var(--gold); font-size: 0.7rem; width: 14px; text-align: center; }

/* ═══════════════════════════════════════════════════════════
   PROMOS
═══════════════════════════════════════════════════════════ */
#promos { background: var(--dark2); }

.promos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-top: 48px;
}

.promo-card {
  background: var(--dark3);
  border-radius: 16px;
  padding: 28px;
  border: 1px solid rgba(255,255,255,0.07);
  position: relative;
  overflow: hidden;
  transition: transform var(--trans);
}
.promo-card:hover { transform: translateY(-4px); }
.promo-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--gold);
}
.promo-discount {
  display: inline-block;
  background: rgba(245,166,35,0.12);
  color: var(--gold);
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 20px;
  margin-bottom: 14px;
}
.promo-title { font-size: 1.1rem; font-weight: 800; margin-bottom: 8px; }
.promo-desc  { font-size: 0.82rem; color: var(--gray-lt); line-height: 1.6; margin-bottom: 16px; }
.promo-expiry { font-size: 0.72rem; color: var(--gray); display: flex; align-items: center; gap: 6px; }

/* ═══════════════════════════════════════════════════════════
   AMENITIES
═══════════════════════════════════════════════════════════ */
#amenities { background: var(--dark); }

.amenities-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-top: 56px;
}

.amenity-card {
  background: var(--dark3);
  border-radius: 14px;
  padding: 28px 20px;
  text-align: center;
  border: 1px solid rgba(255,255,255,0.06);
  transition: transform var(--trans), border-color var(--trans);
}
.amenity-card:hover {
  transform: translateY(-4px);
  border-color: rgba(245,166,35,0.3);
}
.amenity-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(245,166,35,0.1);
  color: var(--gold);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  margin: 0 auto 16px;
}
.amenity-name { font-size: 0.88rem; font-weight: 700; margin-bottom: 6px; }
.amenity-desc { font-size: 0.75rem; color: var(--gray-lt); line-height: 1.55; }

/* ═══════════════════════════════════════════════════════════
   TRAINERS
═══════════════════════════════════════════════════════════ */
#trainers { background: var(--dark2); }

.trainers-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
  margin-top: 56px;
}

.trainer-card {
  background: var(--dark3);
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.07);
  transition: transform var(--trans);
}
.trainer-card:hover { transform: translateY(-6px); }

.trainer-img {
  width: 100%;
  height: 280px;
  object-fit: cover;
  background: var(--dark4);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 5rem;
  color: rgba(255,255,255,0.1);
}

.trainer-body { padding: 20px; }
.trainer-name { font-size: 1rem; font-weight: 800; margin-bottom: 4px; }
.trainer-role { font-size: 0.78rem; color: var(--gold); font-weight: 600; margin-bottom: 10px; }
.trainer-bio  { font-size: 0.78rem; color: var(--gray-lt); line-height: 1.6; margin-bottom: 14px; }
.trainer-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.trainer-tag  { background: rgba(255,255,255,0.06); font-size: 0.68rem; padding: 3px 10px; border-radius: 20px; color: rgba(255,255,255,0.6); }

/* ═══════════════════════════════════════════════════════════
   GALLERY
═══════════════════════════════════════════════════════════ */
#gallery { background: var(--dark); }

.gallery-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  grid-template-rows: repeat(2, 200px);
  gap: 12px;
  margin-top: 48px;
}
.gallery-item {
  background: var(--dark4);
  border-radius: 10px;
  overflow: hidden;
  position: relative;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255,255,255,0.1);
  font-size: 2.5rem;
}
.gallery-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}
.gallery-item:hover img { transform: scale(1.07); }
.gallery-item:first-child { grid-column: span 2; grid-row: span 2; font-size: 4rem; }

.gallery-overlay {
  position: absolute;
  inset: 0;
  background: rgba(245,166,35,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s;
  font-size: 1.5rem;
  color: var(--dark);
}
.gallery-item:hover .gallery-overlay { opacity: 1; }

/* ═══════════════════════════════════════════════════════════
   TESTIMONIALS
═══════════════════════════════════════════════════════════ */
#testimonials { background: var(--dark2); }

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin-top: 56px;
}

.testimonial-card {
  background: var(--dark3);
  border-radius: 16px;
  padding: 28px;
  border: 1px solid rgba(255,255,255,0.07);
  position: relative;
}
.testimonial-card::before {
  content: '\201C';
  position: absolute;
  top: 16px; right: 24px;
  font-family: Georgia, serif;
  font-size: 5rem;
  line-height: 1;
  color: rgba(245,166,35,0.12);
  pointer-events: none;
}
.testimonial-stars { display: flex; gap: 4px; color: var(--gold); font-size: 0.78rem; margin-bottom: 14px; }
.testimonial-text  { font-size: 0.88rem; color: rgba(255,255,255,0.75); line-height: 1.7; margin-bottom: 20px; font-style: italic; }
.testimonial-author { display: flex; align-items: center; gap: 12px; }
.testimonial-avatar {
  width: 40px; height: 40px;
  border-radius: 50%;
  background: var(--dark4);
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem;
  border: 2px solid rgba(245,166,35,0.3);
  overflow: hidden;
}
.testimonial-name { font-size: 0.85rem; font-weight: 700; }
.testimonial-since { font-size: 0.72rem; color: var(--gray-lt); }

/* ═══════════════════════════════════════════════════════════
   CTA BANNER
═══════════════════════════════════════════════════════════ */
#cta-banner {
  background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dk) 100%);
  padding: 80px 0;
  text-align: center;
  position: relative;
  overflow: hidden;
}
#cta-banner::before {
  content: '';
  position: absolute;
  inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23000000' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.cta-title { font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 900; color: var(--dark); position: relative; margin-bottom: 12px; }
.cta-sub { font-size: 1.05rem; color: rgba(13,13,13,0.7); position: relative; margin-bottom: 36px; }
.cta-btns { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; position: relative; }
.btn-dark { background: var(--dark); color: var(--white); }
.btn-dark:hover { background: #222; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.4); }
.btn-white { background: var(--white); color: var(--dark); }
.btn-white:hover { background: var(--off-wh); transform: translateY(-2px); }

/* ═══════════════════════════════════════════════════════════
   CONTACT
═══════════════════════════════════════════════════════════ */
#contact { background: var(--dark); }

.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1.4fr;
  gap: 60px;
  align-items: start;
}

.contact-info { }
.contact-info-item {
  display: flex;
  gap: 16px;
  margin-bottom: 28px;
}
.contact-info-icon {
  width: 46px; height: 46px;
  border-radius: 10px;
  background: rgba(245,166,35,0.1);
  color: var(--gold);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}
.contact-info-label { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--gray-lt); margin-bottom: 4px; }
.contact-info-value { font-size: 0.92rem; color: var(--white); }

.contact-social { display: flex; gap: 12px; margin-top: 32px; }
.social-btn {
  width: 42px; height: 42px;
  border-radius: 10px;
  background: var(--dark3);
  border: 1px solid rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  transition: var(--trans);
  cursor: pointer;
}
.social-btn:hover { background: var(--gold); color: var(--dark); border-color: var(--gold); transform: translateY(-2px); }

/* Contact Form */
.contact-form {
  background: var(--dark3);
  border-radius: 20px;
  padding: 36px;
  border: 1px solid rgba(255,255,255,0.08);
}
.contact-form h3 { font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; }
.contact-form p  { font-size: 0.85rem; color: var(--gray-lt); margin-bottom: 28px; }

.form-group { margin-bottom: 18px; }
.form-label { display: block; font-size: 0.78rem; font-weight: 600; color: rgba(255,255,255,0.7); margin-bottom: 8px; letter-spacing: 0.04em; }
.form-control {
  width: 100%;
  background: var(--dark4);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  padding: 12px 16px;
  color: var(--white);
  font-family: inherit;
  font-size: 0.88rem;
  transition: border-color var(--trans);
  outline: none;
  resize: vertical;
}
.form-control::placeholder { color: rgba(255,255,255,0.25); }
.form-control:focus { border-color: var(--gold); }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

.form-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23F5A623' d='M6 8L0 0h12z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px; }

.form-success {
  display: none;
  background: rgba(34,197,94,0.1);
  border: 1px solid rgba(34,197,94,0.3);
  color: var(--green);
  border-radius: 8px;
  padding: 12px 16px;
  font-size: 0.85rem;
  margin-top: 14px;
  align-items: center;
  gap: 10px;
}
.form-success.show { display: flex; }

/* ═══════════════════════════════════════════════════════════
   MAP
═══════════════════════════════════════════════════════════ */
.map-wrap {
  margin-top: 70px;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.08);
  height: 360px;
  background: var(--dark3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--gray);
  font-size: 0.88rem;
  position: relative;
}
.map-placeholder {
  text-align: center;
}
.map-placeholder i { font-size: 2.5rem; color: var(--gold); display: block; margin-bottom: 12px; }

/* ═══════════════════════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════════════════════ */
footer {
  background: var(--dark2);
  border-top: 1px solid rgba(255,255,255,0.06);
  padding-top: 64px;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.8fr 1fr 1fr 1fr;
  gap: 48px;
  padding-bottom: 48px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.footer-brand { }
.footer-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.footer-logo img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; border: 2px solid var(--gold); }
.footer-logo-text { font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; letter-spacing: 0.04em; }
.footer-logo-text span { color: var(--gold); }
.footer-desc { font-size: 0.83rem; color: var(--gray-lt); line-height: 1.7; margin-bottom: 20px; max-width: 280px; }

.footer-col h4 { font-size: 0.78rem; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.5); margin-bottom: 18px; }
.footer-links { list-style: none; }
.footer-links li { margin-bottom: 10px; }
.footer-links a { font-size: 0.83rem; color: var(--gray-lt); transition: color var(--trans); }
.footer-links a:hover { color: var(--gold); }

.footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 0;
  font-size: 0.78rem;
  color: var(--gray);
}
.footer-bottom a { color: var(--gold); }

/* ═══════════════════════════════════════════════════════════
   FLOATING BOOK BUTTON
═══════════════════════════════════════════════════════════ */
#fab {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 999;
  background: var(--gold);
  color: var(--dark);
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  box-shadow: 0 8px 32px rgba(245,166,35,0.5);
  cursor: pointer;
  transition: transform var(--trans), box-shadow var(--trans);
  border: none;
  animation: fab-bounce 3s ease-in-out infinite;
}
#fab:hover { transform: scale(1.12); box-shadow: 0 12px 40px rgba(245,166,35,0.65); }
@keyframes fab-bounce { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }

/* ═══════════════════════════════════════════════════════════
   BOOKING MODAL
═══════════════════════════════════════════════════════════ */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.85);
  backdrop-filter: blur(8px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s;
}
.modal-overlay.open {
  opacity: 1;
  pointer-events: all;
}

.modal-box {
  background: var(--dark3);
  border-radius: 20px;
  border: 1px solid rgba(255,255,255,0.1);
  max-width: 520px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  transform: translateY(20px);
  transition: transform 0.3s;
}
.modal-overlay.open .modal-box { transform: none; }

.modal-box::-webkit-scrollbar { width: 4px; }
.modal-box::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 2px; }

.modal-header {
  padding: 24px 28px 0;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 6px;
}
.modal-header h3 { font-size: 1.3rem; font-weight: 800; }
.modal-header p  { font-size: 0.83rem; color: var(--gray-lt); }
.modal-close {
  background: rgba(255,255,255,0.07);
  border: none;
  color: var(--white);
  width: 34px; height: 34px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  transition: background var(--trans);
  flex-shrink: 0;
}
.modal-close:hover { background: rgba(255,255,255,0.14); }
.modal-body { padding: 20px 28px 28px; }

/* Step indicator */
.steps {
  display: flex;
  align-items: center;
  gap: 0;
  margin-bottom: 28px;
}
.step {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
}
.step-num {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: var(--dark4);
  border: 2px solid rgba(255,255,255,0.15);
  color: rgba(255,255,255,0.4);
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--trans);
  flex-shrink: 0;
}
.step.active .step-num  { background: var(--gold); border-color: var(--gold); color: var(--dark); }
.step.done .step-num    { background: var(--green); border-color: var(--green); color: var(--white); }
.step-label { font-size: 0.72rem; font-weight: 600; color: rgba(255,255,255,0.4); transition: color var(--trans); }
.step.active .step-label { color: var(--gold); }
.step.done .step-label   { color: rgba(255,255,255,0.6); }
.step-line { flex: 1; height: 1px; background: rgba(255,255,255,0.1); margin: 0 8px; }

/* Plan selection cards */
.plan-cards { display: flex; flex-direction: column; gap: 12px; }
.plan-card-select {
  border: 2px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: border-color var(--trans), background var(--trans);
  display: flex;
  align-items: center;
  gap: 16px;
}
.plan-card-select:hover { border-color: rgba(245,166,35,0.4); background: rgba(245,166,35,0.04); }
.plan-card-select.selected { border-color: var(--gold); background: rgba(245,166,35,0.08); }
.plan-card-select input[type=radio] { display: none; }
.plan-radio { width: 18px; height: 18px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2); flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: var(--trans); }
.plan-card-select.selected .plan-radio { border-color: var(--gold); background: var(--gold); }
.plan-radio-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--dark); display: none; }
.plan-card-select.selected .plan-radio-dot { display: block; }
.plan-card-info { flex: 1; }
.plan-card-name { font-size: 0.9rem; font-weight: 700; margin-bottom: 2px; }
.plan-card-desc { font-size: 0.75rem; color: var(--gray-lt); }
.plan-card-price { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; color: var(--gold); white-space: nowrap; }

/* GCash payment section */
.gcash-box {
  background: var(--dark4);
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  margin-bottom: 20px;
  border: 1px solid rgba(255,255,255,0.08);
}
.gcash-logo {
  background: #007bff;
  color: white;
  font-size: 1.4rem;
  font-weight: 900;
  border-radius: 10px;
  padding: 8px 20px;
  display: inline-block;
  margin-bottom: 14px;
  letter-spacing: 0.03em;
}
.gcash-number { font-size: 1.4rem; font-weight: 800; color: var(--gold); letter-spacing: 0.06em; margin: 8px 0; }
.gcash-name   { font-size: 0.8rem; color: var(--gray-lt); }
.gcash-amount { font-size: 0.82rem; color: rgba(255,255,255,0.5); margin-top: 8px; }

.modal-footer-btns { display: flex; gap: 12px; margin-top: 24px; }
.modal-footer-btns .btn { flex: 1; justify-content: center; }

/* ═══════════════════════════════════════════════════════════
   SCROLL ANIMATIONS
═══════════════════════════════════════════════════════════ */
.reveal { opacity: 0; transform: translateY(32px); transition: opacity 0.65s ease, transform 0.65s ease; }
.reveal.visible { opacity: 1; transform: none; }
.reveal-delay-1 { transition-delay: 0.1s; }
.reveal-delay-2 { transition-delay: 0.2s; }
.reveal-delay-3 { transition-delay: 0.3s; }

/* ═══════════════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .programs-grid    { grid-template-columns: repeat(2, 1fr); }
  .amenities-grid   { grid-template-columns: repeat(2, 1fr); }
  .trainers-grid    { grid-template-columns: repeat(2, 1fr); }
  .footer-grid      { grid-template-columns: 1fr 1fr; gap: 36px; }
}

@media (max-width: 768px) {
  :root { --nav-h: 62px; }
  .section { padding: 64px 0; }

  .nav-links, .nav-cta { display: none; }
  .hamburger { display: flex; }

  .hero-title { font-size: clamp(2.8rem, 10vw, 4.5rem); }
  .hero-stats { gap: 28px; }

  .about-grid   { grid-template-columns: 1fr; gap: 40px; }
  .about-badge  { right: 0; bottom: -20px; }
  .about-img-main { height: 300px; }
  .about-features { grid-template-columns: 1fr; }

  .programs-grid      { grid-template-columns: 1fr; }
  .testimonials-grid  { grid-template-columns: 1fr; }
  .trainers-grid      { grid-template-columns: 1fr; }
  .amenities-grid     { grid-template-columns: repeat(2, 1fr); }

  .gallery-grid { grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(3, 160px); }
  .gallery-item:first-child { grid-column: span 2; grid-row: span 1; }

  .contact-grid { grid-template-columns: 1fr; gap: 40px; }
  .form-row     { grid-template-columns: 1fr; }

  .footer-grid  { grid-template-columns: 1fr; }
  .footer-bottom { flex-direction: column; gap: 10px; text-align: center; }

  .hero-btns { flex-direction: column; align-items: flex-start; }
  .hero-btns .btn { width: 100%; justify-content: center; }
}

@media (max-width: 480px) {
  .amenities-grid { grid-template-columns: 1fr; }
  .hero-stats { flex-wrap: wrap; gap: 20px; }
  .modal-box { border-radius: 16px; }
  .modal-header, .modal-body { padding-left: 20px; padding-right: 20px; }
  .steps { gap: 0; }
  .step-label { display: none; }
  #fab { bottom: 20px; right: 20px; }
}
</style>
</head>
<body>

<!-- ═══ NAVBAR ═══ -->
<nav id="navbar">
  <div class="container">
    <div class="nav-inner">
      <a href="#hero" class="nav-logo">
        <img src="../<?php echo $logo_path; ?>" alt="<?php echo $gym_name; ?>" onerror="this.style.display='none'">
        <div class="nav-logo-text"><?php echo strtoupper($gym_name); ?></div>
      </a>

      <ul class="nav-links">
        <li><a href="#about">About</a></li>
        <li><a href="#programs">Membership</a></li>
        <li><a href="#promos">Promos</a></li>
        <li><a href="#amenities">Amenities</a></li>
        <li><a href="#trainers">Trainers</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>

      <div class="nav-cta">
        <a href="#contact" class="btn btn-outline btn-sm">Inquire</a>
        <a href="#programs" class="btn btn-gold btn-sm" onclick="openBooking()">Join Now</a>
      </div>

      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobile-menu">
  <ul>
    <li><a href="#about"     onclick="closeMobileMenu()">About</a></li>
    <li><a href="#programs"  onclick="closeMobileMenu()">Membership</a></li>
    <li><a href="#promos"    onclick="closeMobileMenu()">Promos</a></li>
    <li><a href="#amenities" onclick="closeMobileMenu()">Amenities</a></li>
    <li><a href="#trainers"  onclick="closeMobileMenu()">Trainers</a></li>
    <li><a href="#contact"   onclick="closeMobileMenu()">Contact</a></li>
  </ul>
  <div class="mobile-cta">
    <a href="#contact" class="btn btn-outline" onclick="closeMobileMenu()"><i class="fas fa-envelope"></i> Inquire</a>
    <button class="btn btn-gold" onclick="closeMobileMenu(); openBooking()"><i class="fas fa-dumbbell"></i> Join Now — Book Online</button>
  </div>
</div>

<!-- ═══ HERO ═══ -->
<section id="hero">
  <div class="hero-bg"></div>
  <div class="hero-grid"></div>
  <div class="container">
    <div class="hero-content">
      <div class="hero-eyebrow">
        <span class="dot"></span>
        Now Accepting Online Membership
      </div>
      <h1 class="hero-title">
        TRAIN<br>
        <span class="gold">HARD.</span><br>
        <span class="outline">LIVE STRONG.</span>
      </h1>
      <p class="hero-sub">
        Olympic Fitness Gym — where champions are made. State-of-the-art equipment, expert coaches, and a community that pushes you to be your best every single day.
      </p>
      <div class="hero-btns">
        <button class="btn btn-gold btn-lg" onclick="openBooking()">
          <i class="fas fa-dumbbell"></i> Join & Pay Online
        </button>
        <a href="#about" class="btn btn-outline btn-lg">
          <i class="fas fa-play-circle"></i> Learn More
        </a>
      </div>
      <div class="hero-stats">
        <div>
          <div class="hero-stat-num" data-count="500">0</div>
          <div class="hero-stat-label">Active Members</div>
        </div>
        <div>
          <div class="hero-stat-num" data-count="10">0</div>
          <div class="hero-stat-label">Expert Trainers</div>
        </div>
        <div>
          <div class="hero-stat-num" data-count="5">0</div>
          <div class="hero-stat-label">Years Strong</div>
        </div>
      </div>
    </div>
  </div>
  <div class="hero-scroll">
    <div class="scroll-line"></div>
    <span>Scroll</span>
  </div>
</section>

<!-- ═══ TICKER ═══ -->
<div class="ticker">
  <div class="ticker-inner" id="ticker-inner">
    <!-- duplicated for infinite scroll -->
    <span class="ticker-item"><i class="fas fa-star"></i> Free Day Pass for First-Timers <i class="fas fa-star"></i></span>
    <span class="ticker-item"><i class="fas fa-bolt"></i> Monthly & Session Membership Available <i class="fas fa-bolt"></i></span>
    <span class="ticker-item"><i class="fas fa-fire"></i> Student Discount Available <i class="fas fa-fire"></i></span>
    <span class="ticker-item"><i class="fas fa-trophy"></i> Open 5AM – 10PM Daily <i class="fas fa-trophy"></i></span>
    <span class="ticker-item"><i class="fas fa-credit-card"></i> Pay Online via GCash <i class="fas fa-credit-card"></i></span>
    <span class="ticker-item"><i class="fas fa-star"></i> Free Day Pass for First-Timers <i class="fas fa-star"></i></span>
    <span class="ticker-item"><i class="fas fa-bolt"></i> Monthly & Session Membership Available <i class="fas fa-bolt"></i></span>
    <span class="ticker-item"><i class="fas fa-fire"></i> Student Discount Available <i class="fas fa-fire"></i></span>
    <span class="ticker-item"><i class="fas fa-trophy"></i> Open 5AM – 10PM Daily <i class="fas fa-trophy"></i></span>
    <span class="ticker-item"><i class="fas fa-credit-card"></i> Pay Online via GCash <i class="fas fa-credit-card"></i></span>
  </div>
</div>

<!-- ═══ ABOUT ═══ -->
<section id="about" class="section">
  <div class="container">
    <div class="about-grid">
      <div class="about-img-wrap reveal">
        <div class="about-img-main" style="background:var(--dark4);display:flex;align-items:center;justify-content:center;font-size:5rem;color:rgba(255,255,255,0.08);overflow:hidden;">
          <?php if (!empty($about_image) && file_exists('../' . $about_image)): ?>
            <img src="../<?php echo htmlspecialchars($about_image); ?>" alt="<?php echo $gym_name; ?>" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <i class="fas fa-dumbbell"></i>
          <?php endif; ?>
        </div>
        <div class="about-badge">
          <div class="about-badge-num">5+</div>
          <div class="about-badge-text">Years of<br>Excellence</div>
        </div>
      </div>
      <div class="reveal reveal-delay-1">
        <div class="section-tag"><i class="fas fa-info-circle"></i> About Us</div>
        <h2 class="section-title">More Than a Gym — <span>A Community</span></h2>
        <p class="section-sub">
          <?php echo $about_text ?: htmlspecialchars($gym_name) . ' was founded with one mission: to make professional-level fitness accessible to everyone in our community. With world-class equipment, certified coaches, and flexible membership plans, we help you achieve real results.'; ?>
        </p>
        <div class="about-features" style="margin-top:28px">
          <div class="about-feat">
            <div class="about-feat-icon"><i class="fas fa-medal"></i></div>
            <div>
              <div class="about-feat-title">Certified Trainers</div>
              <div class="about-feat-sub">All coaches are nationally certified</div>
            </div>
          </div>
          <div class="about-feat">
            <div class="about-feat-icon"><i class="fas fa-clock"></i></div>
            <div>
              <div class="about-feat-title">Open Daily</div>
              <div class="about-feat-sub">5:00 AM – 10:00 PM every day</div>
            </div>
          </div>
          <div class="about-feat">
            <div class="about-feat-icon"><i class="fas fa-shield-alt"></i></div>
            <div>
              <div class="about-feat-title">Safe & Clean</div>
              <div class="about-feat-sub">Sanitized equipment daily</div>
            </div>
          </div>
          <div class="about-feat">
            <div class="about-feat-icon"><i class="fas fa-wifi"></i></div>
            <div>
              <div class="about-feat-title">Free WiFi</div>
              <div class="about-feat-sub">Fast connection throughout</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ PROGRAMS / MEMBERSHIPS ═══ -->
<section id="programs" class="section">
  <div class="container">
    <div class="text-center reveal">
      <div class="section-tag"><i class="fas fa-id-card"></i> Membership Plans</div>
      <h2 class="section-title">Choose Your <span>Plan</span></h2>
      <p class="section-sub">Flexible membership options that fit your schedule and budget. All plans include full gym access.</p>
    </div>
    <div class="programs-grid" id="programs-grid">
      <!-- Loaded dynamically from DB via PHP / static fallback below -->
      <div class="program-card reveal reveal-delay-1">
        <div class="program-body">
          <div class="program-icon"><i class="fas fa-bolt"></i></div>
          <div class="program-name">Per Session</div>
          <div class="program-desc">Perfect for those who prefer flexibility. Pay only for the sessions you use.</div>
          <div class="program-price-row">
            <div class="program-price" id="price-session">₱150</div>
            <div class="program-price-period">/ session</div>
          </div>
          <ul class="program-features">
            <li><i class="fas fa-check"></i> Full gym access</li>
            <li><i class="fas fa-check"></i> Locker use</li>
            <li><i class="fas fa-check"></i> No commitment</li>
            <li><i class="fas fa-times" style="color:var(--gray)"></i> No trainer guidance</li>
          </ul>
          <button class="btn btn-outline" style="width:100%;justify-content:center" onclick="openBooking('session')">
            <i class="fas fa-arrow-right"></i> Get Started
          </button>
        </div>
      </div>

      <div class="program-card featured reveal reveal-delay-2">
        <div class="program-badge">Most Popular</div>
        <div class="program-body">
          <div class="program-icon"><i class="fas fa-calendar-alt"></i></div>
          <div class="program-name">Monthly</div>
          <div class="program-desc">Our best value plan. Unlimited access for the whole month to reach your goals faster.</div>
          <div class="program-price-row">
            <div class="program-price" id="price-monthly">₱800</div>
            <div class="program-price-period">/ month</div>
          </div>
          <ul class="program-features">
            <li><i class="fas fa-check"></i> Unlimited gym access</li>
            <li><i class="fas fa-check"></i> Locker use</li>
            <li><i class="fas fa-check"></i> Free fitness assessment</li>
            <li><i class="fas fa-check"></i> Student discount eligible</li>
          </ul>
          <button class="btn btn-gold" style="width:100%;justify-content:center" onclick="openBooking('monthly')">
            <i class="fas fa-dumbbell"></i> Join Now
          </button>
        </div>
      </div>

      <div class="program-card reveal reveal-delay-3">
        <div class="program-body">
          <div class="program-icon"><i class="fas fa-graduation-cap"></i></div>
          <div class="program-name">Student Plan</div>
          <div class="program-desc">Special discounted rate for students with valid school ID. Learn and train together.</div>
          <div class="program-price-row">
            <div class="program-price" id="price-student">₱640</div>
            <div class="program-price-period">/ month</div>
          </div>
          <ul class="program-features">
            <li><i class="fas fa-check"></i> Unlimited gym access</li>
            <li><i class="fas fa-check"></i> 20% student discount</li>
            <li><i class="fas fa-check"></i> Valid school ID required</li>
            <li><i class="fas fa-check"></i> Locker use</li>
          </ul>
          <button class="btn btn-outline" style="width:100%;justify-content:center" onclick="openBooking('student')">
            <i class="fas fa-arrow-right"></i> Get Started
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ PROMOS ═══ -->
<section id="promos" class="section-sm">
  <div class="container">
    <div class="text-center reveal">
      <div class="section-tag"><i class="fas fa-fire"></i> Current Promos</div>
      <h2 class="section-title">Special <span>Offers</span></h2>
      <p class="section-sub">Limited-time deals and discounts — grab them before they're gone!</p>
    </div>
    <div class="promos-grid" id="promos-grid">
      <div class="promo-card reveal reveal-delay-1">
        <div class="promo-discount">20% OFF</div>
        <div class="promo-title">Student Discount</div>
        <div class="promo-desc">Present a valid school ID and get 20% off on your monthly membership. Available all year round for enrolled students.</div>
        <div class="promo-expiry"><i class="fas fa-calendar-check"></i> Ongoing promo</div>
      </div>
      <div class="promo-card reveal reveal-delay-2">
        <div class="promo-discount">FREE</div>
        <div class="promo-title">First Day Free Trial</div>
        <div class="promo-desc">First-timers get a free day pass to try out our facilities. No strings attached — just walk in and train!</div>
        <div class="promo-expiry"><i class="fas fa-calendar-check"></i> Ongoing promo</div>
      </div>
      <div class="promo-card reveal reveal-delay-3">
        <div class="promo-discount">BUNDLE</div>
        <div class="promo-title">Refer a Friend</div>
        <div class="promo-desc">Refer a friend who signs up for a monthly plan and both of you get 1 week free added to your membership.</div>
        <div class="promo-expiry"><i class="fas fa-calendar-check"></i> Ask at front desk</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ AMENITIES ═══ -->
<section id="amenities" class="section">
  <div class="container">
    <div class="text-center reveal">
      <div class="section-tag"><i class="fas fa-star"></i> Facilities</div>
      <h2 class="section-title">World-Class <span>Amenities</span></h2>
      <p class="section-sub">Everything you need for a complete fitness experience, all under one roof.</p>
    </div>
    <div class="amenities-grid" style="margin-top:48px">
      <div class="amenity-card reveal reveal-delay-1">
        <div class="amenity-icon"><i class="fas fa-dumbbell"></i></div>
        <div class="amenity-name">Free Weights Area</div>
        <div class="amenity-desc">Complete set of dumbbells, barbells, and plates for all your strength training needs.</div>
      </div>
      <div class="amenity-card reveal reveal-delay-2">
        <div class="amenity-icon"><i class="fas fa-running"></i></div>
        <div class="amenity-name">Cardio Zone</div>
        <div class="amenity-desc">Treadmills, stationary bikes, ellipticals, and rowing machines — all with built-in entertainment.</div>
      </div>
      <div class="amenity-card reveal reveal-delay-1">
        <div class="amenity-icon"><i class="fas fa-compress-arrows-alt"></i></div>
        <div class="amenity-name">Machine Area</div>
        <div class="amenity-desc">Full circuit of cable machines, leg press, chest press, and isolation equipment.</div>
      </div>
      <div class="amenity-card reveal reveal-delay-2">
        <div class="amenity-icon"><i class="fas fa-shower"></i></div>
        <div class="amenity-name">Locker Rooms</div>
        <div class="amenity-desc">Clean, secure lockers and shower facilities so you can freshen up after your workout.</div>
      </div>
      <div class="amenity-card reveal reveal-delay-1">
        <div class="amenity-icon"><i class="fas fa-music"></i></div>
        <div class="amenity-name">Sound System</div>
        <div class="amenity-desc">High-energy music throughout the gym to keep you motivated during every rep.</div>
      </div>
      <div class="amenity-card reveal reveal-delay-2">
        <div class="amenity-icon"><i class="fas fa-snowflake"></i></div>
        <div class="amenity-name">Air-Conditioned</div>
        <div class="amenity-desc">Fully air-conditioned facility to keep you comfortable during even the most intense sessions.</div>
      </div>
      <div class="amenity-card reveal reveal-delay-1">
        <div class="amenity-icon"><i class="fas fa-video"></i></div>
        <div class="amenity-name">24/7 CCTV</div>
        <div class="amenity-desc">Your safety is our priority. Complete surveillance throughout the facility.</div>
      </div>
      <div class="amenity-card reveal reveal-delay-2">
        <div class="amenity-icon"><i class="fas fa-wifi"></i></div>
        <div class="amenity-name">Free WiFi</div>
        <div class="amenity-desc">High-speed internet access so you can stream your workouts or stay connected.</div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ TRAINERS ═══ -->
<section id="trainers" class="section">
  <div class="container">
    <div class="text-center reveal">
      <div class="section-tag"><i class="fas fa-users"></i> Our Team</div>
      <h2 class="section-title">Meet Your <span>Coaches</span></h2>
      <p class="section-sub">Certified, experienced, and passionate about helping you reach your fitness goals.</p>
    </div>
    <div class="trainers-grid">
      <?php
      // Fall back to sample trainers only if none have been added in Content management
      $trainers_to_show = $db_trainers ?: [
          ['name' => 'Coach Mike Santos', 'role' => 'Head Strength Coach', 'bio' => '12 years of experience in powerlifting and bodybuilding. NSCA-certified with a passion for building strength foundations.', 'tags' => 'Powerlifting,Bodybuilding,Nutrition', 'image_path' => ''],
          ['name' => 'Coach Anna Reyes', 'role' => 'Cardio & Wellness Coach', 'bio' => 'Certified group fitness instructor specializing in HIIT, Zumba, and endurance training. 8 years in the industry.', 'tags' => 'HIIT,Zumba,Weight Loss', 'image_path' => ''],
          ['name' => 'Coach Renz Dela Cruz', 'role' => 'Functional Training Coach', 'bio' => 'Former competitive athlete with expertise in sports conditioning, mobility, and injury prevention programs.', 'tags' => 'Sports Performance,Mobility,Rehab', 'image_path' => ''],
      ];
      foreach ($trainers_to_show as $i => $t):
          $delay = ($i % 3) + 1;
      ?>
      <div class="trainer-card reveal reveal-delay-<?= $delay ?>">
        <div class="trainer-img">
          <?php if (!empty($t['image_path']) && file_exists('../' . $t['image_path'])): ?>
            <img src="../<?= htmlspecialchars($t['image_path']) ?>" alt="<?= htmlspecialchars($t['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <i class="fas fa-user"></i>
          <?php endif; ?>
        </div>
        <div class="trainer-body">
          <div class="trainer-name"><?= htmlspecialchars($t['name']) ?></div>
          <div class="trainer-role"><?= htmlspecialchars($t['role']) ?></div>
          <div class="trainer-bio"><?= htmlspecialchars($t['bio']) ?></div>
          <?php if (!empty($t['tags'])): ?>
          <div class="trainer-tags">
            <?php foreach (array_filter(array_map('trim', explode(',', $t['tags']))) as $tag): ?>
              <span class="trainer-tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ GALLERY ═══ -->
<section id="gallery" class="section-sm">
  <div class="container">
    <div class="text-center reveal">
      <div class="section-tag"><i class="fas fa-images"></i> Gallery</div>
      <h2 class="section-title">See the <span>Gym</span></h2>
    </div>
    <div class="gallery-grid" style="margin-top:40px">
      <?php
      // Fall back to icon placeholders only if no photos have been added in Content management
      $fallback_icons = ['dumbbell','running','fist-raised','bicycle','heartbeat','medal','trophy'];
      if ($db_gallery):
          foreach ($db_gallery as $i => $g):
              $delay = ($i % 3) + 1;
      ?>
        <div class="gallery-item reveal reveal-delay-<?= $delay ?>">
          <?php if (file_exists('../' . $g['image_path'])): ?>
            <img src="../<?= htmlspecialchars($g['image_path']) ?>" alt="<?= htmlspecialchars($g['caption']) ?>" style="width:100%;height:100%;object-fit:cover;">
          <?php else: ?>
            <i class="fas fa-image"></i>
          <?php endif; ?>
          <div class="gallery-overlay"><i class="fas fa-search-plus"></i><?php if ($g['caption']): ?><span style="display:block;font-size:.7rem;margin-top:6px;"><?= htmlspecialchars($g['caption']) ?></span><?php endif; ?></div>
        </div>
      <?php
          endforeach;
      else:
          foreach ($fallback_icons as $i => $icon):
              $delay = ($i % 3) + 1;
      ?>
        <div class="gallery-item reveal reveal-delay-<?= $delay ?>">
          <i class="fas fa-<?= $icon ?>"></i>
          <div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
        </div>
      <?php
          endforeach;
      endif;
      ?>
    </div>
  </div>
</section>

<!-- ═══ TESTIMONIALS ═══ -->
<section id="testimonials" class="section">
  <div class="container">
    <div class="text-center reveal">
      <div class="section-tag"><i class="fas fa-heart"></i> Testimonials</div>
      <h2 class="section-title">What Our <span>Members Say</span></h2>
    </div>
    <div class="testimonials-grid" style="margin-top:48px">
      <div class="testimonial-card reveal reveal-delay-1">
        <div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
        <p class="testimonial-text">"Best gym in the area! The equipment is always clean and the trainers are super helpful. Lost 15kg in 3 months thanks to Coach Mike's program."</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar"><i class="fas fa-user"></i></div>
          <div>
            <div class="testimonial-name">Maria Santos</div>
            <div class="testimonial-since">Member since 2023</div>
          </div>
        </div>
      </div>
      <div class="testimonial-card reveal reveal-delay-2">
        <div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
        <p class="testimonial-text">"As a student, the discounted rate really helps. The gym has everything I need and the staff are always friendly. Highly recommend Olympic Fitness!"</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar"><i class="fas fa-user"></i></div>
          <div>
            <div class="testimonial-name">Carlo Mendoza</div>
            <div class="testimonial-since">Member since 2024</div>
          </div>
        </div>
      </div>
      <div class="testimonial-card reveal reveal-delay-3">
        <div class="testimonial-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
        <p class="testimonial-text">"I've been to many gyms but Olympic Fitness feels like home. The community here motivates you to keep going even on tough days."</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar"><i class="fas fa-user"></i></div>
          <div>
            <div class="testimonial-name">Liza Fernandez</div>
            <div class="testimonial-since">Member since 2022</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ CTA BANNER ═══ -->
<section id="cta-banner">
  <div class="container">
    <h2 class="cta-title">Ready to Start Your Journey?</h2>
    <p class="cta-sub">Join hundreds of members already transforming their lives at Olympic Fitness Gym.</p>
    <div class="cta-btns">
      <button class="btn btn-dark btn-lg" onclick="openBooking()">
        <i class="fas fa-dumbbell"></i> Book & Pay Online
      </button>
      <a href="#contact" class="btn btn-white btn-lg">
        <i class="fas fa-phone"></i> Talk to Us First
      </a>
    </div>
  </div>
</section>

<!-- ═══ CONTACT ═══ -->
<section id="contact" class="section">
  <div class="container">
    <div class="text-center reveal">
      <div class="section-tag"><i class="fas fa-envelope"></i> Get In Touch</div>
      <h2 class="section-title">Contact <span>Us</span></h2>
      <p class="section-sub">Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
    </div>
    <div class="contact-grid" style="margin-top:56px">
      <div class="reveal">
        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div>
            <div class="contact-info-label">Address</div>
            <div class="contact-info-value"><?php echo $address ?: 'Olympic Fitness Gym, Your City, Philippines'; ?></div>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-phone"></i></div>
          <div>
            <div class="contact-info-label">Phone / GCash</div>
            <div class="contact-info-value"><?php echo $phone ?: '+63 XXX XXX XXXX'; ?></div>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-envelope"></i></div>
          <div>
            <div class="contact-info-label">Email</div>
            <div class="contact-info-value"><?php echo $email_addr ?: 'your@email.com'; ?></div>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-clock"></i></div>
          <div>
            <div class="contact-info-label">Hours</div>
            <div class="contact-info-value"><?php echo $hours; ?></div>
          </div>
        </div>
        <div class="contact-social">
          <a href="<?php echo $fb_url; ?>" class="social-btn" aria-label="Facebook" <?php echo $fb_url==='#'?'':'target="_blank"'; ?>><i class="fab fa-facebook-f"></i></a>
          <a href="<?php echo $ig_url; ?>" class="social-btn" aria-label="Instagram" <?php echo $ig_url==='#'?'':'target="_blank"'; ?>><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-btn" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
          <a href="<?php echo $fb_url; ?>" class="social-btn" aria-label="Messenger"><i class="fab fa-facebook-messenger"></i></a>
        </div>
      </div>

      <div class="contact-form reveal reveal-delay-1">
        <h3>Send a Message</h3>
        <p>Fill out the form below and we'll get back to you within 24 hours.</p>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control" id="contact-name" placeholder="Juan dela Cruz">
          </div>
          <div class="form-group">
            <label class="form-label">Phone Number *</label>
            <input type="tel" class="form-control" id="contact-phone" placeholder="09XX XXX XXXX">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" class="form-control" id="contact-email" placeholder="you@email.com">
        </div>
        <div class="form-group">
          <label class="form-label">Subject *</label>
          <select class="form-control form-select" id="contact-subject">
            <option value="">Select a topic...</option>
            <option>Membership Inquiry</option>
            <option>Pricing & Promos</option>
            <option>Personal Training</option>
            <option>GCash / Online Payment</option>
            <option>General Question</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Message *</label>
          <textarea class="form-control" id="contact-message" rows="4" placeholder="How can we help you?"></textarea>
        </div>
        <button class="btn btn-gold" style="width:100%;justify-content:center" onclick="submitContact()">
          <i class="fas fa-paper-plane"></i> Send Message
        </button>
        <div class="form-success" id="contact-success">
          <i class="fas fa-check-circle"></i>
          Message sent! We'll get back to you within 24 hours.
        </div>
      </div>
    </div>

    <!-- Map placeholder -->
    <div class="map-wrap reveal" style="margin-top:60px">
      <?php if ($map_embed): ?>
      <iframe src="<?php echo htmlspecialchars($map_embed); ?>" width="100%" height="360" style="border:0;display:block" allowfullscreen loading="lazy"></iframe>
      <?php else: ?>
      <div class="map-placeholder">
        <i class="fas fa-map-marker-alt"></i>
        <p>Map coming soon</p>
        <p style="font-size:.76rem;margin-top:6px;color:var(--gray)">Admin can set Google Maps embed in Website Settings</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer>
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-logo">
          <img src="../<?php echo $logo_path; ?>" alt="Logo" onerror="this.style.display='none'">
          <div class="footer-logo-text"><?php echo strtoupper($gym_name); ?></div>
        </div>
        <p class="footer-desc">Your premier fitness destination. Train with the best equipment, expert coaches, and a community that never quits.</p>
        <div class="contact-social">
          <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-btn"><i class="fab fa-tiktok"></i></a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul class="footer-links">
          <li><a href="#about">About Us</a></li>
          <li><a href="#programs">Membership Plans</a></li>
          <li><a href="#promos">Promos & Discounts</a></li>
          <li><a href="#amenities">Facilities</a></li>
          <li><a href="#trainers">Our Trainers</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Membership</h4>
        <ul class="footer-links">
          <li><a href="#" onclick="openBooking('session')">Per Session</a></li>
          <li><a href="#" onclick="openBooking('monthly')">Monthly Plan</a></li>
          <li><a href="#" onclick="openBooking('student')">Student Discount</a></li>
          <li><a href="#contact">Inquire Now</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <ul class="footer-links">
          <li><a href="#">+63 XXX XXX XXXX</a></li>
          <li><a href="#">olympicfitnessgym@email.com</a></li>
          <li><a href="#">Open Daily 5AM–10PM</a></li>
          <li><a href="#contact">Send a Message</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?php echo date('Y'); ?> <?php echo $gym_name; ?>. All rights reserved.</span>
      <span>Designed for <a href="#"><?php echo $gym_name; ?></a></span>
      <a href="../index.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--gray);" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='var(--gray)'" title="Staff & Admin Login">
        <i class="fas fa-lock" style="font-size:.7rem;"></i> Staff Login
      </a>
    </div>
  </div>
</footer>

<!-- ═══ FLOATING ACTION BUTTON ═══ -->
<button id="fab" onclick="openBooking()" title="Join & Pay Online" aria-label="Book membership">
  <i class="fas fa-dumbbell"></i>
</button>

<!-- ═══ BOOKING MODAL ═══ -->
<div class="modal-overlay" id="booking-modal">
  <div class="modal-box">
    <div class="modal-header">
      <div>
        <h3>Join Olympic Fitness Gym</h3>
        <p>Book & pay online via GCash — fast and easy!</p>
      </div>
      <button class="modal-close" onclick="closeBooking()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <!-- Steps -->
      <div class="steps">
        <div class="step active" id="step-1">
          <div class="step-num">1</div>
          <div class="step-label">Choose Plan</div>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step-2">
          <div class="step-num">2</div>
          <div class="step-label">Your Info</div>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step-3">
          <div class="step-num">3</div>
          <div class="step-label">Pay via GCash</div>
        </div>
        <div class="step-line"></div>
        <div class="step" id="step-4">
          <div class="step-num"><i class="fas fa-check" style="font-size:.6rem"></i></div>
          <div class="step-label">Done!</div>
        </div>
      </div>

      <!-- Step 1: Choose plan -->
      <div id="booking-step-1">
        <div class="plan-cards">
          <label class="plan-card-select" onclick="selectPlan(this,'session',150)">
            <input type="radio" name="plan" value="session">
            <div class="plan-radio"><div class="plan-radio-dot"></div></div>
            <div class="plan-card-info">
              <div class="plan-card-name">Per Session</div>
              <div class="plan-card-desc">Single visit, no commitment</div>
            </div>
            <div class="plan-card-price">₱150</div>
          </label>
          <label class="plan-card-select" onclick="selectPlan(this,'monthly',800)">
            <input type="radio" name="plan" value="monthly">
            <div class="plan-radio"><div class="plan-radio-dot"></div></div>
            <div class="plan-card-info">
              <div class="plan-card-name">Monthly Membership</div>
              <div class="plan-card-desc">Unlimited access for 1 month</div>
            </div>
            <div class="plan-card-price">₱800</div>
          </label>
          <label class="plan-card-select" onclick="selectPlan(this,'student',640)">
            <input type="radio" name="plan" value="student">
            <div class="plan-radio"><div class="plan-radio-dot"></div></div>
            <div class="plan-card-info">
              <div class="plan-card-name">Student Monthly</div>
              <div class="plan-card-desc">20% discount with valid school ID</div>
            </div>
            <div class="plan-card-price">₱640</div>
          </label>
        </div>
        <div class="modal-footer-btns" style="margin-top:20px">
          <button class="btn btn-gold" onclick="goStep(2)" id="btn-next-1" disabled>
            Next <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </div>

      <!-- Step 2: Personal Info -->
      <div id="booking-step-2" style="display:none">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" class="form-control" id="book-name" placeholder="Juan dela Cruz">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number (GCash) *</label>
          <input type="tel" class="form-control" id="book-phone" placeholder="09XX XXX XXXX">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" class="form-control" id="book-email" placeholder="you@email.com">
        </div>
        <div class="form-group">
          <label class="form-label">Preferred Start Date *</label>
          <input type="date" class="form-control" id="book-start-date" min="">
          <div style="font-size:.72rem;color:var(--gray-lt);margin-top:6px"><i class="fas fa-info-circle me-1"></i>Choose the date you want your membership to begin.</div>
        </div>
        <div class="form-group" id="student-id-group" style="display:none">
          <label class="form-label">School / Student ID Number *</label>
          <input type="text" class="form-control" id="book-student-id" placeholder="e.g. 2024-00001">
        </div>
        <div class="modal-footer-btns">
          <button class="btn btn-outline" onclick="goStep(1)"><i class="fas fa-arrow-left"></i> Back</button>
          <button class="btn btn-gold" onclick="goStep(3)">Next <i class="fas fa-arrow-right"></i></button>
        </div>
      </div>

      <!-- Step 3: GCash Payment -->
      <div id="booking-step-3" style="display:none">
        <div class="gcash-box">
          <div class="gcash-logo">G<span style="font-size:.7em">Cash</span></div>
          <p style="font-size:.8rem;color:rgba(255,255,255,.5);margin-bottom:8px">Send payment to:</p>
          <div class="gcash-number"><?php echo $gcash_num; ?></div>
          <div class="gcash-name"><?php echo $gcash_name; ?></div>
          <div class="gcash-amount">Amount: <strong id="gcash-amount-display" style="color:var(--gold)">₱0</strong></div>
        </div>
        <div class="form-group">
          <label class="form-label">GCash Reference Number *</label>
          <input type="text" class="form-control" id="book-ref" placeholder="e.g. 1234567890">
          <div style="font-size:.72rem;color:var(--gray-lt);margin-top:6px"><i class="fas fa-info-circle me-1"></i>You'll find this in your GCash receipt after sending.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Upload GCash Screenshot *</label>
          <input type="file" class="form-control" id="book-screenshot" accept="image/*" required>
          <div style="font-size:.72rem;color:var(--gray-lt);margin-top:6px"><i class="fas fa-info-circle me-1"></i>Required as proof of payment — attach your GCash receipt screenshot.</div>
        </div>
        <!-- Booking error box — shown when API returns an error (e.g. active membership) -->
        <div id="booking-error-box" style="display:none;margin-bottom:14px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:12px;padding:14px 16px;">
          <div style="display:flex;align-items:flex-start;gap:10px;">
            <div style="width:32px;height:32px;border-radius:8px;background:#dc2626;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
              <i class="fas fa-exclamation-triangle" style="color:#fff;font-size:.85rem;"></i>
            </div>
            <div>
              <div style="font-weight:700;color:#991b1b;font-size:.95rem;margin-bottom:.3rem;">Booking Not Submitted</div>
              <div id="booking-error-msg" style="color:#b91c1c;font-size:.88rem;line-height:1.5;"></div>
              <div style="margin-top:.6rem;font-size:.82rem;color:#991b1b;">
                Need help? <a href="#contact" style="color:#991b1b;text-decoration:underline;" onclick="document.querySelector('.modal-overlay').style.display='none'">Contact us</a> or visit the gym directly.
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer-btns">
          <button class="btn btn-outline" onclick="goStep(2)"><i class="fas fa-arrow-left"></i> Back</button>
          <button class="btn btn-gold" onclick="submitBooking()"><i class="fas fa-paper-plane"></i> Submit Booking</button>
        </div>
      </div>

      <!-- Step 4: Success -->
      <div id="booking-step-4" style="display:none;padding:0">
        <!-- Confirmation slip -->
        <div style="background:var(--dark4);border-radius:12px;padding:18px;margin-bottom:16px;border:1px solid rgba(245,166,35,.25)">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
            <div style="width:38px;height:38px;border-radius:50%;background:rgba(34,197,94,.15);border:2px solid var(--green);display:flex;align-items:center;justify-content:center;color:var(--green);font-size:1.1rem;flex-shrink:0"><i class="fas fa-check"></i></div>
            <div>
              <div style="font-size:1rem;font-weight:700;color:#fff">Booking Submitted!</div>
              <div style="font-size:.72rem;color:var(--gray-lt)">Keep this as your proof of booking</div>
            </div>
          </div>

          <div style="background:rgba(0,0,0,.25);border-radius:8px;padding:14px;font-size:.8rem;line-height:2;border:1px dashed rgba(255,255,255,.1)">
            <div style="text-align:center;font-size:.65rem;text-transform:uppercase;letter-spacing:.08em;color:var(--gray-lt);margin-bottom:8px">BOOKING CONFIRMATION</div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:6px;margin-bottom:6px">
              <span style="color:var(--gray-lt)">Booking ID</span>
              <span style="color:var(--gold);font-weight:700;" id="conf-booking-id">#—</span>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:6px;margin-bottom:6px">
              <span style="color:var(--gray-lt)">Name</span>
              <span style="color:#fff;font-weight:600;" id="conf-name">—</span>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:6px;margin-bottom:6px">
              <span style="color:var(--gray-lt)">Plan</span>
              <span style="color:#fff;" id="conf-plan">—</span>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:6px;margin-bottom:6px">
              <span style="color:var(--gray-lt)">Amount Paid</span>
              <span style="color:var(--green);font-weight:700;" id="conf-amount">—</span>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:6px;margin-bottom:6px">
              <span style="color:var(--gray-lt)">GCash Ref#</span>
              <span style="color:#fff;font-family:monospace;" id="conf-gcash">—</span>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:6px;margin-bottom:6px">
              <span style="color:var(--gray-lt)">Preferred Start</span>
              <span style="color:#fff;" id="conf-start">—</span>
            </div>
            <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.07);padding-bottom:6px;margin-bottom:6px">
              <span style="color:var(--gray-lt)">Submitted</span>
              <span style="color:#fff;font-size:.72rem;" id="conf-submitted">—</span>
            </div>
            <div style="margin-top:8px;padding-top:8px;border-top:1px dashed rgba(255,255,255,.1);font-size:.68rem;color:var(--gray-lt)">
              Confirmation Token<br>
              <span style="font-family:monospace;color:rgba(255,255,255,.5);word-break:break-all;font-size:.65rem;" id="conf-token">—</span>
            </div>
          </div>

          <div style="margin-top:12px;font-size:.75rem;color:var(--gray-lt);line-height:1.7;text-align:center">
            ✅ Payment pending verification &nbsp;·&nbsp; ⏱ Usually 1–2 hours<br>
            📋 Screenshot this or <a id="conf-print-link" href="#" target="_blank" style="color:var(--gold)">open printable version</a>
          </div>
        </div>

        <div style="display:flex;gap:8px">
          <a id="conf-print-link2" href="#" target="_blank" class="btn btn-outline" style="flex:1;justify-content:center;font-size:.82rem" onclick="document.getElementById('conf-print-link2').href=document.getElementById('conf-print-link').href">
            <i class="fas fa-print"></i> Print / Save PDF
          </a>
          <button class="btn btn-gold" style="flex:1;justify-content:center;font-size:.82rem" onclick="closeBooking()">
            <i class="fas fa-times"></i> Close
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SITE ALERT MODAL (replaces native browser alert popups) ═══ -->
<div class="modal-overlay" id="site-alert-modal">
  <div class="modal-box" style="max-width:420px;">
    <div style="padding:32px 28px 28px;text-align:center;">
      <div id="site-alert-icon" style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <i class="fas fa-exclamation-circle" style="font-size:1.5rem;"></i>
      </div>
      <h4 id="site-alert-title" style="color:var(--white);font-weight:800;font-size:1.15rem;margin-bottom:10px;">Notice</h4>
      <p id="site-alert-message" style="color:var(--gray-lt);font-size:.9rem;line-height:1.6;margin-bottom:26px;"></p>
      <button class="btn btn-gold" style="width:100%;justify-content:center;" onclick="closeSiteAlert()">
        <i class="fas fa-check"></i> OK
      </button>
    </div>
  </div>
</div>

<script>
// ═══ SITE ALERT MODAL — themed replacement for native browser alert() ═══
function siteAlert(message, type) {
  type = type || 'error';
  const styles = {
    error:   { icon: 'fa-exclamation-circle', bg: 'rgba(220,38,38,0.15)',  color: '#ef4444', title: 'Something Went Wrong' },
    success: { icon: 'fa-check-circle',       bg: 'rgba(34,197,94,0.15)', color: '#22c55e', title: 'Success' },
    info:    { icon: 'fa-info-circle',        bg: 'rgba(245,166,35,0.15)', color: 'var(--gold)', title: 'Notice' }
  };
  const s = styles[type] || styles.error;
  const iconBox = document.getElementById('site-alert-icon');
  iconBox.style.background = s.bg;
  iconBox.innerHTML = '<i class="fas ' + s.icon + '" style="font-size:1.5rem;color:' + s.color + ';"></i>';
  document.getElementById('site-alert-title').textContent = s.title;
  // Strip any leading "Error: " prefix since the modal already communicates severity visually
  document.getElementById('site-alert-message').textContent = String(message).replace(/^Error:\s*/i, '');
  document.getElementById('site-alert-modal').classList.add('open');
}
function closeSiteAlert() {
  document.getElementById('site-alert-modal').classList.remove('open');
}

// ═══ LOAD LIVE PRICES & PROMOS FROM API ══════════════════
function loadLiveData() {
  // Load prices
  fetch('api/membership_fees.php')
    .then(r => r.json())
    .then(d => {
      if (!d.success) return;
      const fmt = n => '₱' + Number(n).toLocaleString();
      // Update pricing cards
      document.getElementById('price-session').textContent = fmt(d.per_session_fee);
      document.getElementById('price-monthly').textContent = fmt(d.monthly_fee);
      document.getElementById('price-student').textContent = fmt(d.student_fee);
      // Update booking modal plan prices
      document.querySelectorAll('.plan-card-select').forEach(card => {
        const val = card.querySelector('input').value;
        const priceEl = card.querySelector('.plan-card-price');
        if (val === 'session')  { priceEl.textContent = fmt(d.per_session_fee); }
        if (val === 'monthly')  { priceEl.textContent = fmt(d.monthly_fee); }
        if (val === 'student')  { priceEl.textContent = fmt(d.student_fee); }
      });
      // Store prices globally
      window._prices = { session: d.per_session_fee, monthly: d.monthly_fee, student: d.student_fee };
    })
    .catch(() => {});

  // Load promos
  fetch('api/promos.php')
    .then(r => r.json())
    .then(d => {
      if (!d.success || !d.promos.length) return;
      const grid = document.getElementById('promos-grid');
      grid.innerHTML = '';
      d.promos.forEach((p, i) => {
        const exp = p.expiry_date ? `<div class="promo-expiry"><i class="fas fa-calendar-alt"></i> Expires: ${p.expiry_date}</div>` : `<div class="promo-expiry"><i class="fas fa-calendar-check"></i> Ongoing promo</div>`;
        const delay = ['reveal-delay-1','reveal-delay-2','reveal-delay-3'][i % 3];
        const card = document.createElement('div');
        card.className = `promo-card reveal ${delay}`;
        card.innerHTML = `${p.discount ? `<div class="promo-discount">${p.discount}</div>` : ''}<div class="promo-title">${p.title}</div><div class="promo-desc">${p.description || ''}</div>${exp}`;
        grid.appendChild(card);
        revealObserver.observe(card);
      });
    })
    .catch(() => {});
}
window.addEventListener('DOMContentLoaded', loadLiveData);

// ═══ NAVBAR SCROLL ════════════════════════════════════════
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 20);
});

// ═══ HAMBURGER ════════════════════════════════════════════
const hamburger   = document.getElementById('hamburger');
const mobileMenu  = document.getElementById('mobile-menu');

hamburger.addEventListener('click', () => {
  hamburger.classList.toggle('open');
  mobileMenu.classList.toggle('open');
});

function closeMobileMenu() {
  hamburger.classList.remove('open');
  mobileMenu.classList.remove('open');
}

// Close on outside click
document.addEventListener('click', e => {
  if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) closeMobileMenu();
});

// ═══ COUNTER ANIMATION ════════════════════════════════════
function animateCounters() {
  document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count);
    const suffix = target >= 500 ? '+' : target === 5 ? '+' : '';
    let current  = 0;
    const step   = Math.max(1, Math.floor(target / 60));
    const timer  = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current + suffix;
      if (current >= target) clearInterval(timer);
    }, 25);
  });
}

// ═══ REVEAL ON SCROLL ═════════════════════════════════════
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// Counter on hero visible
new IntersectionObserver((entries) => {
  if (entries[0].isIntersecting) animateCounters();
}, { threshold: 0.5 }).observe(document.getElementById('hero'));

// ═══ ACTIVE NAV LINK ══════════════════════════════════════
const sections  = document.querySelectorAll('section[id]');
const navLinks  = document.querySelectorAll('.nav-links a');

window.addEventListener('scroll', () => {
  let current = '';
  sections.forEach(s => {
    if (window.scrollY >= s.offsetTop - 100) current = s.id;
  });
  navLinks.forEach(a => {
    a.classList.toggle('active', a.getAttribute('href') === '#' + current);
  });
}, { passive: true });

// ═══ BOOKING MODAL ════════════════════════════════════════
let selectedPlan  = null;
let selectedPrice = 0;
let currentStep   = 1;

function openBooking(plan) {
  document.getElementById('booking-modal').classList.add('open');
  document.body.style.overflow = 'hidden';
  goStep(1);
  if (plan) {
    // Pre-select the plan
    document.querySelectorAll('.plan-card-select').forEach(card => {
      const val = card.querySelector('input').value;
      if (val === plan) {
        const prices = { session: 150, monthly: 800, student: 640 };
        selectPlan(card, plan, prices[plan]);
      }
    });
  }
}

function closeBooking() {
  document.getElementById('booking-modal').classList.remove('open');
  document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('booking-modal').addEventListener('click', function(e) {
  if (e.target === this) closeBooking();
});

function selectPlan(el, plan, fallbackPrice) {
  document.querySelectorAll('.plan-card-select').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  selectedPlan  = plan;
  // Use live price if available
  const live = window._prices || {};
  selectedPrice = live[plan] || fallbackPrice;
  document.getElementById('btn-next-1').disabled = false;
  document.getElementById('gcash-amount-display').textContent = '₱' + Number(selectedPrice).toLocaleString();
  document.getElementById('student-id-group').style.display = plan === 'student' ? 'block' : 'none';
}

function goStep(n) {
  hideBookingError();
  for (let i = 1; i <= 4; i++) {
    const stepEl    = document.getElementById('booking-step-' + i);
    const stepIndEl = document.getElementById('step-' + i);
    if (stepEl) stepEl.style.display = i === n ? 'block' : 'none';
    if (stepIndEl) {
      stepIndEl.classList.remove('active', 'done');
      if (i === n) stepIndEl.classList.add('active');
      if (i < n)  stepIndEl.classList.add('done');
    }
  }
  currentStep = n;
}

// Set minimum date for start date to today
(function() {
  var sd = document.getElementById('book-start-date');
  if (sd) { sd.min = new Date().toISOString().split('T')[0]; sd.value = new Date().toISOString().split('T')[0]; }
})();

function showBookingError(msg) {
  const box = document.getElementById('booking-error-box');
  document.getElementById('booking-error-msg').textContent = msg;
  box.style.display = 'block';
  box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function hideBookingError() {
  const box = document.getElementById('booking-error-box');
  if (box) box.style.display = 'none';
}

function submitBooking() {
  const name    = document.getElementById('book-name').value.trim();
  const phone   = document.getElementById('book-phone').value.trim();
  const email   = document.getElementById('book-email').value.trim();
  const ref     = document.getElementById('book-ref').value.trim();
  const studId  = document.getElementById('book-student-id').value.trim();
  const ssFile  = document.getElementById('book-screenshot').files[0];

  if (!name || !phone || !email) { siteAlert('Please fill in all required fields.', 'info'); goStep(2); return; }
  if (!ref) { siteAlert('Please enter your GCash reference number.', 'info'); return; }
  if (!ssFile) { siteAlert('Please upload a screenshot of your GCash payment as proof of payment.', 'info'); return; }
  if (selectedPlan === 'student' && !studId) { siteAlert('Please enter your student ID.', 'info'); return; }

  const startDate = document.getElementById('book-start-date').value;
  if (!startDate) { siteAlert('Please select your preferred start date.', 'info'); goStep(2); return; }

  const fd = new FormData();
  fd.append('name',                 name);
  fd.append('phone',                phone);
  fd.append('email',                email);
  fd.append('plan',                 selectedPlan);
  fd.append('amount',               selectedPrice);
  fd.append('gcash_ref',            ref);
  fd.append('student_id',           studId);
  fd.append('preferred_start_date', startDate);
  fd.append('screenshot', ssFile);

  fetch('api/book.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        // Populate confirmation slip
        document.getElementById('conf-booking-id').textContent  = '#' + data.booking_id;
        document.getElementById('conf-name').textContent        = data.full_name;
        document.getElementById('conf-plan').textContent        = data.plan_type.charAt(0).toUpperCase() + data.plan_type.slice(1) + ' Plan';
        document.getElementById('conf-amount').textContent      = '\u20b1' + data.amount;
        document.getElementById('conf-gcash').textContent       = data.gcash_ref;
        document.getElementById('conf-start').textContent       = data.preferred_start_date;
        document.getElementById('conf-submitted').textContent   = data.submitted_at;
        document.getElementById('conf-token').textContent       = data.confirmation_token;
        document.getElementById('conf-print-link').href         = '../booking_confirmation.php?token=' + data.confirmation_token;
        hideBookingError();
        goStep(4);
      } else {
        showBookingError(data.error || 'Something went wrong. Please try again.');
      }
    })
    .catch(() => showBookingError('Network error. Please check your connection and try again.'));
}

// ═══ CONTACT FORM ═════════════════════════════════════════
function submitContact() {
  const name    = document.getElementById('contact-name').value.trim();
  const phone   = document.getElementById('contact-phone').value.trim();
  const subject = document.getElementById('contact-subject').value;
  const message = document.getElementById('contact-message').value.trim();

  if (!name || !phone || !subject || !message) {
    siteAlert('Please fill in all required fields.', 'info'); return;
  }

  const fd = new FormData();
  fd.append('name',    name);
  fd.append('phone',   phone);
  fd.append('email',   document.getElementById('contact-email').value.trim());
  fd.append('subject', subject);
  fd.append('message', message);

  fetch('api/contact.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) { document.getElementById('contact-success').classList.add('show'); }
      else { siteAlert(data.error || 'Please try again.', 'error'); }
    })
    .catch(() => siteAlert('Network error. Please try again.', 'error'));
  document.getElementById('contact-name').value    = '';
  document.getElementById('contact-phone').value   = '';
  document.getElementById('contact-email').value   = '';
  document.getElementById('contact-subject').value = '';
  document.getElementById('contact-message').value = '';
}

// Close modal on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeBooking();
});
</script>
</body>
</html>
