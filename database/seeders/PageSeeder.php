<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Policy and informational pages.
 *
 * These are seeded so that every footer link resolves to real, readable content
 * on a fresh install — a storefront whose Terms link 404s looks broken, and the
 * checkout links to the terms on the same screen it asks for payment.
 *
 * The content is illustrative and should be reviewed by a qualified adviser
 * before it is relied on. Nigerian consumer-protection and data-protection law
 * (the FCCPA and the NDPA) impose specific obligations that a template cannot
 * anticipate for every business.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $position => $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'excerpt' => $page['excerpt'],
                    'group' => $page['group'],
                    'status' => PageStatus::Published,
                    'published_at' => now(),
                    'position' => $position,
                    'show_in_footer' => true,
                    'is_indexable' => true,
                    'meta_title' => $page['title'].' — '.config('hanbell.name'),
                    'meta_description' => $page['excerpt'],
                    'llm_summary' => $page['excerpt'],
                ],
            );
        }
    }

    /** @return array<int,array<string,string>> */
    private function pages(): array
    {
        return [
            [
                'slug' => 'about',
                'title' => 'About HanbellShop',
                'group' => 'general',
                'excerpt' => 'HanbellShop is a multi-vendor marketplace for fashion made by indigenous Nigerian brands and creators, sold at fair and affordable prices.',
                'content' => <<<'HTML'
<p>HanbellShop exists for one reason: to put fashion made by Nigerian hands in front of people who will love it, at a price that is fair to the person wearing it and to the person who made it.</p>

<h2>What we do</h2>
<p>We work with independent Nigerian brands, designers, tailors, weavers and makers. Every item on HanbellShop is made in Nigeria. Each listing names the brand behind it, and where we know it, the city it was made in.</p>

<h2>Why our prices are what they are</h2>
<p>Most of what you pay reaches the maker, because we sell directly rather than through layers of middlemen. We charge a straightforward commission on each sale. There are no listing fees and no monthly charge, so a small studio can list three pieces or three hundred without it costing them anything up front.</p>

<h2>Quality</h2>
<p>Every listing is reviewed by hand before it goes live, and every brand is approved individually. If something is not right, we would rather not sell it.</p>

<h2>Get in touch</h2>
<p>If you make things, we would like to hear from you. Start at our <a href="/pages/sell-with-us">sell with us</a> page, or email us any time.</p>
HTML,
            ],
            [
                'slug' => 'terms-of-service',
                'title' => 'Terms of Service',
                'group' => 'policy',
                'excerpt' => 'The terms that govern your use of HanbellShop, including orders, pricing, delivery and your rights.',
                'content' => <<<'HTML'
<p>These terms govern your use of HanbellShop. By placing an order or creating an account you agree to them. Please read them before you buy.</p>

<h2>1. About these terms</h2>
<p>HanbellShop is a marketplace. Independent brands ("vendors") list and sell their own items through this platform. When you buy, your contract for the goods is with the vendor; HanbellShop provides the platform, takes payment, and handles customer support on the vendor's behalf.</p>

<h2>2. Accounts</h2>
<p>You need an account to check out and to track orders. Keep your password secure, and tell us straight away if you think someone else has access to your account. We recommend turning on two-step verification in your account settings.</p>

<h2>3. Prices and payment</h2>
<p>Prices are shown in Nigerian Naira by default and include VAT where it applies. Delivery is charged separately and is shown before you pay. We accept payment through the providers listed at checkout. A payment is only treated as complete once our payment provider has confirmed it to us directly — we never rely on a browser message alone.</p>

<h2>4. Orders</h2>
<p>An order is accepted when we confirm it and your payment is verified. If an item becomes unavailable after you order, we will contact you and refund that item in full. Some items are made to order; where that is the case, the listing will say so and the maker will confirm the timeline.</p>

<h2>5. Delivery</h2>
<p>We deliver across Nigeria and offer international shipping to selected destinations. Estimated delivery windows are estimates, not guarantees, but we will tell you if something is delayed. Risk passes to you on delivery.</p>

<h2>6. Returns and refunds</h2>
<p>Your rights to cancel and return an order are set out in our <a href="/pages/returns-policy">Returns Policy</a>, which forms part of these terms.</p>

<h2>7. Using HanbellShop</h2>
<p>Please do not misuse the platform: do not attempt to break it, scrape it, or use it to break the law. We may suspend an account that does.</p>

<h2>8. Our liability</h2>
<p>We take care to keep HanbellShop accurate and available, but we do not promise it will be uninterrupted or error-free. Nothing in these terms limits any right you have under Nigerian consumer law that cannot be limited.</p>

<h2>9. Changes</h2>
<p>We may update these terms. If we make a significant change we will tell you before it takes effect. The version that applies to your order is the one published when you placed it.</p>

<h2>10. Contact</h2>
<p>Questions about these terms? Email us and we will respond within two working days.</p>
HTML,
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'group' => 'policy',
                'excerpt' => 'What personal data HanbellShop collects, why we collect it, how we protect it, and the rights you have over it.',
                'content' => <<<'HTML'
<p>This policy explains what personal data we collect, why we collect it, and what you can ask us to do with it. We collect as little as we can and keep it only as long as we need it.</p>

<h2>What we collect</h2>
<ul>
<li><strong>Account details</strong> — your name, email address and password. Passwords are stored only as a one-way hash; nobody at HanbellShop can read yours.</li>
<li><strong>Order and delivery details</strong> — the address and phone number needed to deliver your order, and what you bought.</li>
<li><strong>Payment information</strong> — handled by our payment providers. We receive a confirmation and a reference; we never receive or store your full card number.</li>
<li><strong>Security data</strong> — sign-in attempts, with the IP address and browser used, so we can detect misuse of your account.</li>
<li><strong>Advertising data</strong> — if you see an advert on the site, we record that it was shown and whether it was clicked. This uses a fingerprint derived from your IP address and browser that is hashed together with the date and cannot be reversed back to your IP address. It also changes every day.</li>
</ul>

<h2>Why we use it</h2>
<p>To take and deliver your order, to keep your account secure, to answer your questions, to prevent fraud, and to meet our legal and accounting obligations. With your consent, we also send occasional emails about new arrivals — you can unsubscribe from any of them in one click.</p>

<h2>Who we share it with</h2>
<p>Only where it is needed to complete your order: the brand fulfilling it, our delivery partners, and our payment providers. We do not sell personal data, and we do not share it for anyone else's marketing.</p>

<h2>How we protect it</h2>
<p>Traffic to HanbellShop is encrypted in transit. Passwords are hashed, and the secrets behind two-step verification and any recovery codes are encrypted at rest, so a database copy alone would not expose them. Access to customer data inside HanbellShop is limited to the staff who need it.</p>

<h2>How long we keep it</h2>
<p>Order records are kept for as long as tax and accounting rules require. Security logs are kept for a limited period and then deleted. You can ask us to delete your account at any time; we will remove everything we are not legally required to keep.</p>

<h2>Your rights</h2>
<p>You can ask for a copy of the data we hold about you, ask us to correct it, or ask us to delete it. You can also object to us using your data for marketing. Email us and we will act on your request within 30 days.</p>

<h2>Cookies</h2>
<p>We use a small number of cookies that are necessary for the site to work — keeping you signed in and remembering your basket and language. See our <a href="/pages/cookie-policy">Cookie Policy</a> for detail.</p>

<h2>Contact</h2>
<p>For any privacy request, email our support address and mark it "Privacy request".</p>
HTML,
            ],
            [
                'slug' => 'returns-policy',
                'title' => 'Returns Policy',
                'group' => 'policy',
                'excerpt' => 'How to return an item, what we can accept, and when your refund is issued.',
                'content' => <<<'HTML'
<p>We want you to be happy with what you bought. If you are not, here is how returns work.</p>

<h2>Your cancellation window</h2>
<p>You may cancel an order for any reason within 14 days of receiving it. Tell us within that window and we will arrange the return. Once we have the item back and have checked it, we refund you in full, including the standard delivery charge you paid.</p>

<h2>What we can accept</h2>
<p>Items must be unworn, unwashed and in the condition you received them, with any tags still attached. This is for hygiene and because many of our pieces are made in very small batches.</p>

<h2>What we cannot accept</h2>
<ul>
<li>Items made to your measurements or personalised for you, unless they are faulty.</li>
<li>Pierced jewellery, for hygiene reasons.</li>
<li>Items returned after the 14-day window.</li>
</ul>

<h2>Faulty or incorrect items</h2>
<p>If something arrives faulty, damaged or is not what you ordered, tell us within 48 hours of delivery and send a photograph if you can. We will cover the cost of returning it and send a replacement or a full refund, whichever you prefer. Your rights here are not limited by the 14-day window.</p>

<h2>How to start a return</h2>
<p>Email us with your order number and what you would like to do. We will reply with return instructions. Please do not send anything back before you hear from us, because returns need to be matched to your order.</p>

<h2>How long a refund takes</h2>
<p>We issue refunds within 3 working days of receiving the returned item. How quickly it reaches you then depends on your bank or payment provider — usually a few more working days. We refund to the method you paid with.</p>

<h2>Return delivery costs</h2>
<p>If you are returning because you changed your mind, you cover the return delivery. If the item was faulty or incorrect, we cover it.</p>
HTML,
            ],
            [
                'slug' => 'shipping-policy',
                'title' => 'Shipping Policy',
                'group' => 'policy',
                'excerpt' => 'Delivery timelines, charges and coverage for orders within Nigeria and internationally.',
                'content' => <<<'HTML'
<p>Here is what to expect once you have ordered.</p>

<h2>Dispatch</h2>
<p>Orders are dispatched within 1–3 working days. Made-to-order pieces take longer; the listing will say so and the maker will confirm the timeline with you directly.</p>

<h2>Delivery within Nigeria</h2>
<p>We deliver nationwide. Standard delivery is a flat fee, and it is free once your basket passes the threshold shown in your bag. Typical delivery is 2–5 working days after dispatch, depending on your state.</p>

<h2>International delivery</h2>
<p>We ship to selected international destinations. International orders may attract customs duties or import taxes on arrival. Those charges are set by your country, not by us, and are your responsibility.</p>

<h2>Tracking</h2>
<p>You will receive a tracking reference by email once your order is on its way, and you can follow the order any time from your account or the order tracking page.</p>

<h2>If something goes wrong</h2>
<p>If your order has not arrived within the estimated window, contact us and we will chase it. If an item is lost in transit, we will replace it or refund you in full. Risk passes to you on delivery, so please check your parcel when it arrives.</p>

<h2>Split deliveries</h2>
<p>If your order contains items from more than one brand, they may arrive separately, because each brand dispatches its own pieces. You will not be charged extra for this.</p>
HTML,
            ],
            [
                'slug' => 'cookie-policy',
                'title' => 'Cookie Policy',
                'group' => 'policy',
                'excerpt' => 'The cookies HanbellShop uses, what each one is for, and how to control them.',
                'content' => <<<'HTML'
<p>Cookies are small files a site stores in your browser. We keep ours to a minimum.</p>

<h2>Strictly necessary cookies</h2>
<p>These are required for the site to work and cannot be switched off:</p>
<ul>
<li><strong>Session</strong> — keeps you signed in as you move between pages.</li>
<li><strong>CSRF token</strong> — protects forms from being submitted by another site on your behalf.</li>
<li><strong>Basket and wishlist</strong> — remembers what you have added before you sign in. These are set as HTTP-only, which means scripts on the page cannot read them.</li>
<li><strong>Language</strong> — remembers the language you chose.</li>
</ul>

<h2>Advertising measurement</h2>
<p>When an advert is shown on the site, we record that it was displayed and whether it was clicked, so brands can see whether their campaigns work. This uses a fingerprint derived from your IP address and browser, combined with the date and hashed. The raw IP address is never stored, and because the date is part of the hash, the identifier changes every day and cannot be used to follow you across sites.</p>

<h2>Managing cookies</h2>
<p>You can block or delete cookies in your browser settings. Blocking the strictly necessary ones will stop parts of the site — including signing in and using the basket — from working.</p>
HTML,
            ],
            [
                'slug' => 'acceptable-use',
                'title' => 'Acceptable Use Policy',
                'group' => 'policy',
                'excerpt' => 'What is and is not acceptable when using HanbellShop, for shoppers and for brands.',
                'content' => <<<'HTML'
<p>HanbellShop works because people use it in good faith. This page sets out what that means.</p>

<h2>For everyone</h2>
<ul>
<li>Do not attempt to gain access to accounts, data or systems you are not authorised to use.</li>
<li>Do not scrape, crawl or bulk-download the site except through the routes we publish for that purpose.</li>
<li>Do not use HanbellShop to break any law, or to sell anything unlawful.</li>
<li>Do not impersonate another person or brand.</li>
</ul>

<h2>For brands selling on HanbellShop</h2>
<ul>
<li>List only items you made or are authorised to sell, and describe them accurately.</li>
<li>State clearly where an item is made.</li>
<li>Do not use another brand's photographs or copy without permission.</li>
<li>Price honestly. A "was" price must be a price the item was genuinely offered at.</li>
<li>Dispatch within the times you commit to, and tell us promptly if you cannot.</li>
</ul>

<h2>Reviews</h2>
<p>Reviews must be your genuine opinion of something you bought. We remove reviews that are abusive, that contain personal information about someone else, or that are written to promote something rather than to describe an experience.</p>

<h2>What happens if this is breached</h2>
<p>We may remove content, cancel orders, or suspend an account. Where something looks criminal, we may report it. We will always tell you what we have done and why, unless doing so would compromise an investigation.</p>
HTML,
            ],
            [
                'slug' => 'size-guide',
                'title' => 'Size Guide',
                'group' => 'help',
                'excerpt' => 'How to measure yourself, and what our sizes mean. Nigerian sizing varies by brand, so always check the listing.',
                'content' => <<<'HTML'
<p>Sizing varies between brands — and our brands cut their own patterns — so the measurements below are a starting point, not a guarantee. Each listing notes anything that runs differently.</p>

<h2>How to measure</h2>
<ul>
<li><strong>Bust or chest</strong> — measure around the fullest part, keeping the tape level and not pulled tight.</li>
<li><strong>Waist</strong> — measure around your natural waist, the narrowest point above your navel.</li>
<li><strong>Hips</strong> — measure around the fullest part of your hips and seat.</li>
<li><strong>Inseam</strong> — from the crotch seam to where you want the hem to sit.</li>
</ul>
<p>Measure over light clothing, and if you are between sizes, go up — most of our pieces can be taken in more easily than they can be let out.</p>

<h2>Women's sizes (cm)</h2>
<table>
<thead><tr><th>Size</th><th>Bust</th><th>Waist</th><th>Hips</th></tr></thead>
<tbody>
<tr><td>XS</td><td>80–84</td><td>62–66</td><td>88–92</td></tr>
<tr><td>S</td><td>85–89</td><td>67–71</td><td>93–97</td></tr>
<tr><td>M</td><td>90–94</td><td>72–76</td><td>98–102</td></tr>
<tr><td>L</td><td>95–100</td><td>77–82</td><td>103–108</td></tr>
<tr><td>XL</td><td>101–107</td><td>83–89</td><td>109–115</td></tr>
<tr><td>XXL</td><td>108–114</td><td>90–96</td><td>116–122</td></tr>
</tbody>
</table>

<h2>Men's sizes (cm)</h2>
<table>
<thead><tr><th>Size</th><th>Chest</th><th>Waist</th><th>Collar</th></tr></thead>
<tbody>
<tr><td>S</td><td>92–96</td><td>76–80</td><td>38</td></tr>
<tr><td>M</td><td>97–101</td><td>81–86</td><td>40</td></tr>
<tr><td>L</td><td>102–107</td><td>87–93</td><td>42</td></tr>
<tr><td>XL</td><td>108–113</td><td>94–100</td><td>44</td></tr>
<tr><td>XXL</td><td>114–120</td><td>101–108</td><td>46</td></tr>
</tbody>
</table>

<h2>Footwear</h2>
<p>Shoes are listed in EU sizes. If you are between sizes in a closed shoe, take the larger; for sandals, take the smaller.</p>

<h2>Still unsure?</h2>
<p>Contact us with the item and your measurements and we will ask the brand. Made-to-measure is also available from several of our brands — the listing will say so.</p>
HTML,
            ],
            [
                'slug' => 'faq',
                'title' => 'Frequently Asked Questions',
                'group' => 'help',
                'excerpt' => 'Answers to the questions we are asked most about ordering, delivery, payment and selling on HanbellShop.',
                'content' => <<<'HTML'
<h2>Where are your items made?</h2>
<p>In Nigeria. Every brand on HanbellShop makes its pieces here, and each listing names the brand and, where we know it, the city of manufacture.</p>

<h2>Why are your prices lower than elsewhere?</h2>
<p>We sell directly rather than through resellers, and we charge brands a straightforward commission instead of listing fees. More of what you pay reaches the person who made the piece.</p>

<h2>Which payment methods do you accept?</h2>
<p>The options shown at checkout depend on the currency you are paying in. Nigerian Naira payments go through local providers; international cards are supported through our international rails. An option only appears if it is genuinely available.</p>

<h2>Do you deliver outside Nigeria?</h2>
<p>Yes, to selected destinations. Customs duties and import taxes are set by your country and are payable by you on arrival.</p>

<h2>How long will my order take?</h2>
<p>Most orders are dispatched within 1–3 working days and delivered 2–5 working days after that within Nigeria. Made-to-order pieces take longer, and the maker will confirm the timeline.</p>

<h2>Can I return something?</h2>
<p>Yes, within 14 days of receiving it, provided it is unworn and in its original condition. See our <a href="/pages/returns-policy">Returns Policy</a> for the detail and the exceptions.</p>

<h2>Is my payment secure?</h2>
<p>Payments are handled by established payment providers and are encrypted in transit. We never see or store your full card details, and an order is only marked as paid once our provider has confirmed the payment to us directly.</p>

<h2>I am a designer. How do I sell on HanbellShop?</h2>
<p>Apply through our <a href="/pages/sell-with-us">sell with us</a> page. Every application is read by a person, and we will come back to you within three working days.</p>

<h2>How do I track my order?</h2>
<p>Use the order tracking page with your order number and the email address you ordered with, or sign in and open your orders.</p>

<h2>Something is wrong with my order</h2>
<p>Contact us with your order number and we will sort it out. If an item arrived faulty or is not what you ordered, we cover the return and refund or replace it.</p>
HTML,
            ],
            [
                'slug' => 'sell-with-us',
                'title' => 'Sell on HanbellShop',
                'group' => 'general',
                'excerpt' => 'Bring your Nigerian fashion brand to shoppers nationwide and internationally. Fair commission, no listing fees.',
                'content' => <<<'HTML'
<p>If you design, cut, sew, weave, dye or make, we would like to hear from you.</p>

<h2>What it costs</h2>
<p>We charge a straightforward commission on each sale. There are no listing fees and no monthly charge, so listing three pieces costs you nothing and listing three hundred costs you nothing either. Commission is agreed when your application is approved, and it is shown on every order.</p>

<h2>What you get</h2>
<ul>
<li>Your own brand page, with your story and your whole catalogue in one place — not buried in an undifferentiated list.</li>
<li>Reach across Nigeria, and international buyers paying in their own currency.</li>
<li>We take the payment, handle the customer support and publish the policies, so you can concentrate on making.</li>
<li>A clear statement showing exactly what sold, what commission was charged, and what is payable to you.</li>
</ul>

<h2>What we ask of you</h2>
<ul>
<li>Make your pieces in Nigeria.</li>
<li>Describe them accurately, including the material and where they were made.</li>
<li>Dispatch within the window you commit to, or tell us promptly if something changes.</li>
<li>Price honestly — a "was" price must be one the item was genuinely offered at.</li>
</ul>

<h2>How to apply</h2>
<p>Fill in the application form with your brand name, where you work, and what you make. A person reads every application, and we will come back to you within three working days. If we need to see samples or photographs, we will ask.</p>

<p><a href="/brands">See the brands already selling on HanbellShop</a>, then apply when you are ready.</p>
HTML,
            ],
        ];
    }
}
