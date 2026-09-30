<?php

/**
 * Curated Unsplash imagery for the demo catalogue.
 *
 * These are real Unsplash photos, referenced by their direct CDN URL
 * (https://images.unsplash.com/photo-...). Unsplash's hotlinking guideline
 * permits serving images straight from images.unsplash.com provided the
 * photographer is credited and linked back to their Unsplash profile, which is
 * why every entry carries both `credit` (the photographer's name) and
 * `credit_url` (their profile).
 *
 * The Unsplash API needs an access key, and source.unsplash.com has been
 * discontinued, so this file is the offline-friendly substitute: a plain array
 * of verified, working image URLs plus the attribution needed to use them.
 *
 * These are DEMO assets. They illustrate what the seeded catalogue looks like;
 * they are not photographs of the products actually being sold. Replace them
 * with real product photography before going live.
 *
 * Every URL is pinned to `?auto=format&fit=crop&w=900&q=80` so the CDN returns a
 * sensibly sized, compressed, cropped JPEG for product cards and banners.
 *
 * Generated file - edit the curation list rather than hand-tweaking entries.
 */

return [
    // Broad lifestyle / hero imagery for banners and homepage sections.
    'hero' => [
        ['url' => 'https://images.unsplash.com/photo-1602794837437-89ab353d5b4a?auto=format&fit=crop&w=900&q=80', 'credit' => 'Amani Nation', 'credit_url' => 'https://unsplash.com/@amani_nation'],
        ['url' => 'https://images.unsplash.com/photo-1505421031134-e57263cae630?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ayo Ogunseinde', 'credit_url' => 'https://unsplash.com/@armedshutter'],
        ['url' => 'https://images.unsplash.com/photo-1696962678565-bee84e6b9cb6?auto=format&fit=crop&w=900&q=80', 'credit' => 'Eduardo Espinoza', 'credit_url' => 'https://unsplash.com/@eduardoee'],
        ['url' => 'https://images.unsplash.com/photo-1784160053632-6eddd51bda26?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
        ['url' => 'https://images.unsplash.com/photo-1787779351084-5cf715888d06?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ben Iwara', 'credit_url' => 'https://unsplash.com/@1hundredimages'],
        ['url' => 'https://images.unsplash.com/photo-1781274054552-311be66203fd?auto=format&fit=crop&w=900&q=80', 'credit' => 'Christian Agbede', 'credit_url' => 'https://unsplash.com/@chriscreations__'],
        ['url' => 'https://images.unsplash.com/photo-1709809081557-78f803ce93a0?auto=format&fit=crop&w=900&q=80', 'credit' => 'LOLA AZIZADA', 'credit_url' => 'https://unsplash.com/@kalpa_mahagamage'],
        ['url' => 'https://images.unsplash.com/photo-1532076904124-d4e8fe7fbbec?auto=format&fit=crop&w=900&q=80', 'credit' => 'Prince Akachi', 'credit_url' => 'https://unsplash.com/@princearkman'],
        ['url' => 'https://images.unsplash.com/photo-1687052001151-316f9356dbc0?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ibrahima Toure', 'credit_url' => 'https://unsplash.com/@gringophoto'],
        ['url' => 'https://images.unsplash.com/photo-1666974932375-90e8a25bc1ef?auto=format&fit=crop&w=900&q=80', 'credit' => 'Max Mota', 'credit_url' => 'https://unsplash.com/@maxmota'],
        ['url' => 'https://images.unsplash.com/photo-1601653233006-5c9fd30eab12?auto=format&fit=crop&w=900&q=80', 'credit' => 'Hush Naidoo Jade Photography', 'credit_url' => 'https://unsplash.com/@hush52'],
        ['url' => 'https://images.unsplash.com/photo-1625646741211-711bdd65c570?auto=format&fit=crop&w=900&q=80', 'credit' => 'Rohan Odhiambo', 'credit_url' => 'https://unsplash.com/@skanky'],
    ],

    // Product photography, grouped by the category slug it fits.
    'products' => [
        'dresses' => [
            ['url' => 'https://images.unsplash.com/photo-1556914249-863ba0ec162d?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ali Pazani', 'credit_url' => 'https://unsplash.com/@alipzn'],
            ['url' => 'https://images.unsplash.com/photo-1561052876-b9dc9f9b1556?auto=format&fit=crop&w=900&q=80', 'credit' => 'Artem Ivanchencko', 'credit_url' => 'https://unsplash.com/@artemivanchencko'],
            ['url' => 'https://images.unsplash.com/photo-1718963904724-d45712e7c864?auto=format&fit=crop&w=900&q=80', 'credit' => 'Branislav Rodman', 'credit_url' => 'https://unsplash.com/@branislavrodman'],
            ['url' => 'https://images.unsplash.com/photo-1789110853872-f416085557fa?auto=format&fit=crop&w=900&q=80', 'credit' => 'engin akyurt', 'credit_url' => 'https://unsplash.com/@enginakyurt'],
            ['url' => 'https://images.unsplash.com/photo-1789110520302-3df8ce0410f0?auto=format&fit=crop&w=900&q=80', 'credit' => 'engin akyurt', 'credit_url' => 'https://unsplash.com/@enginakyurt'],
            ['url' => 'https://images.unsplash.com/photo-1789110853833-bc1a883da2d6?auto=format&fit=crop&w=900&q=80', 'credit' => 'engin akyurt', 'credit_url' => 'https://unsplash.com/@enginakyurt'],
            ['url' => 'https://images.unsplash.com/photo-1779398969439-99c38b9df638?auto=format&fit=crop&w=900&q=80', 'credit' => 'ola szkolda', 'credit_url' => 'https://unsplash.com/@olaszkolda'],
            ['url' => 'https://images.unsplash.com/photo-1624588483556-851e7726b9fd?auto=format&fit=crop&w=900&q=80', 'credit' => 'Rosa Rafael', 'credit_url' => 'https://unsplash.com/@rosarafael'],
            ['url' => 'https://images.unsplash.com/photo-1768460608433-d3af5148832c?auto=format&fit=crop&w=900&q=80', 'credit' => 'style grid', 'credit_url' => 'https://unsplash.com/@stylegrid_30'],
            ['url' => 'https://images.unsplash.com/photo-1612336307429-8a898d10e223?auto=format&fit=crop&w=900&q=80', 'credit' => 'Süheyl Burak', 'credit_url' => 'https://unsplash.com/@suheylburak'],
            ['url' => 'https://images.unsplash.com/photo-1562349486-3355f0c8cefa?auto=format&fit=crop&w=900&q=80', 'credit' => 'Vladimir Fedotov', 'credit_url' => 'https://unsplash.com/@fedotov_vs'],
            ['url' => 'https://images.unsplash.com/photo-1628144029346-8a98676311b6?auto=format&fit=crop&w=900&q=80', 'credit' => 'Amani Nation', 'credit_url' => 'https://unsplash.com/@amani_nation'],
            ['url' => 'https://images.unsplash.com/photo-1784160053635-424cf07f567c?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
        ],
        'agbada' => [
            ['url' => 'https://images.unsplash.com/photo-1788035963223-06abb95a1289?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1788035962539-a1d6ef60ec48?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1788035963488-cbfabe82ed4a?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1782566208081-6b5135fddf23?auto=format&fit=crop&w=900&q=80', 'credit' => 'Sunday Oludare', 'credit_url' => 'https://unsplash.com/@thewizbeat_'],
            ['url' => 'https://images.unsplash.com/photo-1763823132521-72f373850de2?auto=format&fit=crop&w=900&q=80', 'credit' => 'Chidera Faustina Okeke', 'credit_url' => 'https://unsplash.com/@thefourthwxll'],
            ['url' => 'https://images.unsplash.com/photo-1688805599802-ab7939632d19?auto=format&fit=crop&w=900&q=80', 'credit' => 'Fatima Yusuf', 'credit_url' => 'https://unsplash.com/@fatima_yusuf'],
            ['url' => 'https://images.unsplash.com/photo-1776880470574-8f14805247f0?auto=format&fit=crop&w=900&q=80', 'credit' => 'Olumide Adekunle', 'credit_url' => 'https://unsplash.com/@0rangevisuals'],
            ['url' => 'https://images.unsplash.com/photo-1776880471112-708c211e6a4b?auto=format&fit=crop&w=900&q=80', 'credit' => 'Olumide Adekunle', 'credit_url' => 'https://unsplash.com/@0rangevisuals'],
            ['url' => 'https://images.unsplash.com/photo-1722481744477-d0b0bb2bbba2?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ayano Tosin', 'credit_url' => 'https://unsplash.com/@peacetoolmedia'],
            ['url' => 'https://images.unsplash.com/photo-1722481743667-4a772dee62cc?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ayano Tosin', 'credit_url' => 'https://unsplash.com/@peacetoolmedia'],
            ['url' => 'https://images.unsplash.com/photo-1731595759478-baaa99a4a6fe?auto=format&fit=crop&w=900&q=80', 'credit' => 'Bruno Ngarukiye', 'credit_url' => 'https://unsplash.com/@itsbrunoagain'],
        ],
        'kaftan' => [
            ['url' => 'https://images.unsplash.com/photo-1784854492449-02196baedaff?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1784854491921-ba757fbb92ad?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1784854492511-86d666c1b3b3?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1784854492627-1b604da46c51?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1784854491921-b95af565764b?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1784854492609-5af64de459e1?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1784854492442-bd4d2afd189b?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1784854491993-16445b10a6c6?auto=format&fit=crop&w=900&q=80', 'credit' => 'McFollis', 'credit_url' => 'https://unsplash.com/@thisismcfollis'],
            ['url' => 'https://images.unsplash.com/photo-1776880471207-d183341477fd?auto=format&fit=crop&w=900&q=80', 'credit' => 'Olumide Adekunle', 'credit_url' => 'https://unsplash.com/@0rangevisuals'],
            ['url' => 'https://images.unsplash.com/photo-1776880470556-21bd84d081e1?auto=format&fit=crop&w=900&q=80', 'credit' => 'Olumide Adekunle', 'credit_url' => 'https://unsplash.com/@0rangevisuals'],
            ['url' => 'https://images.unsplash.com/photo-1776880470534-2e19345ab02b?auto=format&fit=crop&w=900&q=80', 'credit' => 'Olumide Adekunle', 'credit_url' => 'https://unsplash.com/@0rangevisuals'],
            ['url' => 'https://images.unsplash.com/photo-1619610406093-f442da3e89e1?auto=format&fit=crop&w=900&q=80', 'credit' => 'Patrick Amofah', 'credit_url' => 'https://unsplash.com/@zerben4all'],
        ],
        'tops' => [
            ['url' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=900&q=80', 'credit' => 'Nimble Made', 'credit_url' => 'https://unsplash.com/@nimblemade'],
            ['url' => 'https://images.unsplash.com/photo-1602810316693-3667c854239a?auto=format&fit=crop&w=900&q=80', 'credit' => 'Nimble Made', 'credit_url' => 'https://unsplash.com/@nimblemade'],
            ['url' => 'https://images.unsplash.com/photo-1603252110481-7ba873bf42ab?auto=format&fit=crop&w=900&q=80', 'credit' => 'Nimble Made', 'credit_url' => 'https://unsplash.com/@nimblemade'],
            ['url' => 'https://images.unsplash.com/photo-1626497764746-6dc36546b388?auto=format&fit=crop&w=900&q=80', 'credit' => 'M. Ghufanil Muta Ali', 'credit_url' => 'https://unsplash.com/@goefan'],
            ['url' => 'https://images.unsplash.com/photo-1548768041-2fceab4c0b85?auto=format&fit=crop&w=900&q=80', 'credit' => 'Mnz', 'credit_url' => 'https://unsplash.com/@mnzoutfits'],
            ['url' => 'https://images.unsplash.com/photo-1523381294911-8d3cead13475?auto=format&fit=crop&w=900&q=80', 'credit' => 'Keagan Henman', 'credit_url' => 'https://unsplash.com/@henmankk'],
            ['url' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=900&q=80', 'credit' => 'Keagan Henman', 'credit_url' => 'https://unsplash.com/@henmankk'],
            ['url' => 'https://images.unsplash.com/photo-1549037173-e3b717902c57?auto=format&fit=crop&w=900&q=80', 'credit' => 'Waldemar Brandt', 'credit_url' => 'https://unsplash.com/@waldemarbrandt67w'],
            ['url' => 'https://images.unsplash.com/photo-1602810319428-019690571b5b?auto=format&fit=crop&w=900&q=80', 'credit' => 'Nimble Made', 'credit_url' => 'https://unsplash.com/@nimblemade'],
            ['url' => 'https://images.unsplash.com/photo-1642764873649-5c228ce3fe74?auto=format&fit=crop&w=900&q=80', 'credit' => 'farhad chaudhary', 'credit_url' => 'https://unsplash.com/@f6655'],
        ],
        'trousers' => [
            ['url' => 'https://images.unsplash.com/photo-1714143136372-ddaf8b606da7?auto=format&fit=crop&w=900&q=80', 'credit' => 'TuanAnh Blue', 'credit_url' => 'https://unsplash.com/@blueeyeaa'],
            ['url' => 'https://images.unsplash.com/photo-1718252540617-6ecda2b56b57?auto=format&fit=crop&w=900&q=80', 'credit' => 'TuanAnh Blue', 'credit_url' => 'https://unsplash.com/@blueeyeaa'],
            ['url' => 'https://images.unsplash.com/photo-1718252540511-e958742e4165?auto=format&fit=crop&w=900&q=80', 'credit' => 'TuanAnh Blue', 'credit_url' => 'https://unsplash.com/@blueeyeaa'],
            ['url' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=900&q=80', 'credit' => 'Mnz', 'credit_url' => 'https://unsplash.com/@mnzoutfits'],
            ['url' => 'https://images.unsplash.com/photo-1605518216938-7c31b7b14ad0?auto=format&fit=crop&w=900&q=80', 'credit' => 'Eduardo Pastor', 'credit_url' => 'https://unsplash.com/@eduardopastor'],
            ['url' => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=900&q=80', 'credit' => 'Jason Leung', 'credit_url' => 'https://unsplash.com/@ninjason'],
            ['url' => 'https://images.unsplash.com/photo-1713880453396-aa0493e308ec?auto=format&fit=crop&w=900&q=80', 'credit' => 'TuanAnh Blue', 'credit_url' => 'https://unsplash.com/@blueeyeaa'],
            ['url' => 'https://images.unsplash.com/photo-1714143136367-7bb68f3f0669?auto=format&fit=crop&w=900&q=80', 'credit' => 'TuanAnh Blue', 'credit_url' => 'https://unsplash.com/@blueeyeaa'],
            ['url' => 'https://images.unsplash.com/photo-1560243563-062bfc001d68?auto=format&fit=crop&w=900&q=80', 'credit' => 'lan deng', 'credit_url' => 'https://unsplash.com/@landall'],
            ['url' => 'https://images.unsplash.com/photo-1714729382668-7bc3bb261662?auto=format&fit=crop&w=900&q=80', 'credit' => 'TuanAnh Blue', 'credit_url' => 'https://unsplash.com/@blueeyeaa'],
            ['url' => 'https://images.unsplash.com/photo-1715758890151-2c15d5d482aa?auto=format&fit=crop&w=900&q=80', 'credit' => 'TuanAnh Blue', 'credit_url' => 'https://unsplash.com/@blueeyeaa'],
        ],
        'skirts' => [
            ['url' => 'https://images.unsplash.com/photo-1789110519584-ce21260be4e6?auto=format&fit=crop&w=900&q=80', 'credit' => 'engin akyurt', 'credit_url' => 'https://unsplash.com/@enginakyurt'],
            ['url' => 'https://images.unsplash.com/photo-1789110854402-7aa666508ef4?auto=format&fit=crop&w=900&q=80', 'credit' => 'engin akyurt', 'credit_url' => 'https://unsplash.com/@enginakyurt'],
            ['url' => 'https://images.unsplash.com/photo-1789110854005-6376fa97be36?auto=format&fit=crop&w=900&q=80', 'credit' => 'engin akyurt', 'credit_url' => 'https://unsplash.com/@enginakyurt'],
            ['url' => 'https://images.unsplash.com/photo-1762343041573-aa2827852bc9?auto=format&fit=crop&w=900&q=80', 'credit' => 'Zulfugar Karimov', 'credit_url' => 'https://unsplash.com/@zulfugarkarimov'],
            ['url' => 'https://images.unsplash.com/photo-1574413230119-f302e1c9035d?auto=format&fit=crop&w=900&q=80', 'credit' => 'Apostolos Vamvouras', 'credit_url' => 'https://unsplash.com/@apostolosv'],
            ['url' => 'https://images.unsplash.com/photo-1574413230698-f80892c96e13?auto=format&fit=crop&w=900&q=80', 'credit' => 'Apostolos Vamvouras', 'credit_url' => 'https://unsplash.com/@apostolosv'],
            ['url' => 'https://images.unsplash.com/photo-1543405075-f0d9e0457476?auto=format&fit=crop&w=900&q=80', 'credit' => 'Tamara Bellis', 'credit_url' => 'https://unsplash.com/@tamarabellis'],
            ['url' => 'https://images.unsplash.com/photo-1553096763-6fb9cdc4df14?auto=format&fit=crop&w=900&q=80', 'credit' => 'Amplitude Magazin', 'credit_url' => 'https://unsplash.com/@amplitudemagazin'],
            ['url' => 'https://images.unsplash.com/photo-1549575810-b9b7abc51d9e?auto=format&fit=crop&w=900&q=80', 'credit' => 'Anton Mislawsky', 'credit_url' => 'https://unsplash.com/@antonmislawsky'],
            ['url' => 'https://images.unsplash.com/photo-1551180452-cc3ca222cdfb?auto=format&fit=crop&w=900&q=80', 'credit' => 'Olga Guryanova', 'credit_url' => 'https://unsplash.com/@designer4u'],
        ],
        'shoes' => [
            ['url' => 'https://images.unsplash.com/photo-1524553879936-2ff074ae5816?auto=format&fit=crop&w=900&q=80', 'credit' => 'Alex Hudson', 'credit_url' => 'https://unsplash.com/@aliffhassan91'],
            ['url' => 'https://images.unsplash.com/photo-1535043934128-cf0b28d52f95?auto=format&fit=crop&w=900&q=80', 'credit' => 'Emily Pottiger', 'credit_url' => 'https://unsplash.com/@anadventure'],
            ['url' => 'https://images.unsplash.com/photo-1591884807537-0bce39888fe0?auto=format&fit=crop&w=900&q=80', 'credit' => 'Birgith Roosipuu', 'credit_url' => 'https://unsplash.com/@msbirgith'],
            ['url' => 'https://images.unsplash.com/photo-1596702874230-b5706dfb5bc7?auto=format&fit=crop&w=900&q=80', 'credit' => 'Laura Chouette', 'credit_url' => 'https://unsplash.com/@laurachouette'],
            ['url' => 'https://images.unsplash.com/photo-1618274158630-bc47a614b3a5?auto=format&fit=crop&w=900&q=80', 'credit' => 'Pesce Huang', 'credit_url' => 'https://unsplash.com/@pesce'],
            ['url' => 'https://images.unsplash.com/photo-1562687848-c1664eff566d?auto=format&fit=crop&w=900&q=80', 'credit' => 'Jonathan Borba', 'credit_url' => 'https://unsplash.com/@jonathanborba'],
            ['url' => 'https://images.unsplash.com/photo-1670607231621-c00fd76d2387?auto=format&fit=crop&w=900&q=80', 'credit' => 'Sandy Millar', 'credit_url' => 'https://unsplash.com/@sandym10'],
            ['url' => 'https://images.unsplash.com/photo-1515347619252-60a4bf4fff4f?auto=format&fit=crop&w=900&q=80', 'credit' => 'Andrew Tanglao', 'credit_url' => 'https://unsplash.com/@andrewtanglao'],
            ['url' => 'https://images.unsplash.com/photo-1562692158-7e4cbe83716f?auto=format&fit=crop&w=900&q=80', 'credit' => 'Andrea Peperó', 'credit_url' => 'https://unsplash.com/@pepero_3'],
            ['url' => 'https://images.unsplash.com/photo-1656164847621-4665c4c397da?auto=format&fit=crop&w=900&q=80', 'credit' => 'HamZa NOUASRIA', 'credit_url' => 'https://unsplash.com/@hamza01nsr'],
            ['url' => 'https://images.unsplash.com/photo-1695073621086-aa692bc32a3d?auto=format&fit=crop&w=900&q=80', 'credit' => 'Sanju Pandita', 'credit_url' => 'https://unsplash.com/@spxclicks'],
            ['url' => 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?auto=format&fit=crop&w=900&q=80', 'credit' => 'Irene Kredenets', 'credit_url' => 'https://unsplash.com/@ikredenets'],
            ['url' => 'https://images.unsplash.com/photo-1628413993904-94ecb60f1239?auto=format&fit=crop&w=900&q=80', 'credit' => 'Deepal Tamang', 'credit_url' => 'https://unsplash.com/@deepal_tamang'],
            ['url' => 'https://images.unsplash.com/photo-1542219550-37153d387c27?auto=format&fit=crop&w=900&q=80', 'credit' => 'Mnz', 'credit_url' => 'https://unsplash.com/@mnzoutfits'],
        ],
        'bags' => [
            ['url' => 'https://images.unsplash.com/photo-1605733513597-a8f8341084e6?auto=format&fit=crop&w=900&q=80', 'credit' => 'mostafa mahmoudi', 'credit_url' => 'https://unsplash.com/@mostafa_mahmoudi24'],
            ['url' => 'https://images.unsplash.com/photo-1473188588951-666fce8e7c68?auto=format&fit=crop&w=900&q=80', 'credit' => 'Álvaro Serrano', 'credit_url' => 'https://unsplash.com/@alvaroserrano'],
            ['url' => 'https://images.unsplash.com/photo-1637759292654-a12cb2be085e?auto=format&fit=crop&w=900&q=80', 'credit' => 'Kristian Bøgh', 'credit_url' => 'https://unsplash.com/@bosswik'],
            ['url' => 'https://images.unsplash.com/photo-1624687943971-e86af76d57de?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ugluk Potroshitel', 'credit_url' => 'https://unsplash.com/@uglug'],
            ['url' => 'https://images.unsplash.com/photo-1691480250099-a63081ecfcb8?auto=format&fit=crop&w=900&q=80', 'credit' => 'personalgraphic.com', 'credit_url' => 'https://unsplash.com/@personal_graphic'],
            ['url' => 'https://images.unsplash.com/photo-1691480150204-66dd1eb77391?auto=format&fit=crop&w=900&q=80', 'credit' => 'personalgraphic.com', 'credit_url' => 'https://unsplash.com/@personal_graphic'],
            ['url' => 'https://images.unsplash.com/photo-1705909237050-7a7625b47fac?auto=format&fit=crop&w=900&q=80', 'credit' => 'Mobina Ghazazani', 'credit_url' => 'https://unsplash.com/@mobinaghzz'],
            ['url' => 'https://images.unsplash.com/photo-1746880223690-359948154c53?auto=format&fit=crop&w=900&q=80', 'credit' => 'Leonie Giardini', 'credit_url' => 'https://unsplash.com/@leoniegiardini'],
            ['url' => 'https://images.unsplash.com/photo-1603219527847-24c87f552a77?auto=format&fit=crop&w=900&q=80', 'credit' => 'Alexandr Sadkov', 'credit_url' => 'https://unsplash.com/@sadkov26'],
            ['url' => 'https://images.unsplash.com/photo-1657603738389-951c374b740c?auto=format&fit=crop&w=900&q=80', 'credit' => 'Fauzan Fathullah', 'credit_url' => 'https://unsplash.com/@fzfte'],
            ['url' => 'https://images.unsplash.com/photo-1732613838357-b6f6931cdb97?auto=format&fit=crop&w=900&q=80', 'credit' => 'Kate Laine', 'credit_url' => 'https://unsplash.com/@kikimora33'],
            ['url' => 'https://images.unsplash.com/photo-1652427019217-3ded1a356f10?auto=format&fit=crop&w=900&q=80', 'credit' => 'Vimal S', 'credit_url' => 'https://unsplash.com/@vimal_saran'],
        ],
        'jewellery' => [
            ['url' => 'https://images.unsplash.com/photo-1601121141499-17ae80afc03a?auto=format&fit=crop&w=900&q=80', 'credit' => 'Vaibhav Nagare', 'credit_url' => 'https://unsplash.com/@vaibhavnagare'],
            ['url' => 'https://images.unsplash.com/photo-1601121141461-9d6647bca1ed?auto=format&fit=crop&w=900&q=80', 'credit' => 'Vaibhav Nagare', 'credit_url' => 'https://unsplash.com/@vaibhavnagare'],
            ['url' => 'https://images.unsplash.com/photo-1601121141418-c1caa10a2a0b?auto=format&fit=crop&w=900&q=80', 'credit' => 'Vaibhav Nagare', 'credit_url' => 'https://unsplash.com/@vaibhavnagare'],
            ['url' => 'https://images.unsplash.com/photo-1589095053205-8fc842336f4a?auto=format&fit=crop&w=900&q=80', 'credit' => 'Sayak Bala', 'credit_url' => 'https://unsplash.com/@sayakbala'],
            ['url' => 'https://images.unsplash.com/photo-1611107683227-e9060eccd846?auto=format&fit=crop&w=900&q=80', 'credit' => 'Syed F Hashemi', 'credit_url' => 'https://unsplash.com/@sfhashemi'],
            ['url' => 'https://images.unsplash.com/photo-1543294001-f7cd5d7fb516?auto=format&fit=crop&w=900&q=80', 'credit' => 'Cornelia Ng', 'credit_url' => 'https://unsplash.com/@corneliang'],
            ['url' => 'https://images.unsplash.com/photo-1655255114527-d0a834d9a774?auto=format&fit=crop&w=900&q=80', 'credit' => 'Jocelyn Morales', 'credit_url' => 'https://unsplash.com/@molnj'],
            ['url' => 'https://images.unsplash.com/photo-1626784215013-13322cb0e471?auto=format&fit=crop&w=900&q=80', 'credit' => 'MAYANK GEHLOT', 'credit_url' => 'https://unsplash.com/@mg_clickers'],
            ['url' => 'https://images.unsplash.com/photo-1722410180687-b05b50922362?auto=format&fit=crop&w=900&q=80', 'credit' => 'PRAHANT STUDIO', 'credit_url' => 'https://unsplash.com/@prahantstudio'],
            ['url' => 'https://images.unsplash.com/photo-1722410180681-9f5a22d7ebb6?auto=format&fit=crop&w=900&q=80', 'credit' => 'PRAHANT STUDIO', 'credit_url' => 'https://unsplash.com/@prahantstudio'],
            ['url' => 'https://images.unsplash.com/photo-1721807644561-9efcabee5c42?auto=format&fit=crop&w=900&q=80', 'credit' => 'PRAHANT STUDIO', 'credit_url' => 'https://unsplash.com/@prahantstudio'],
            ['url' => 'https://images.unsplash.com/photo-1727947074642-0bd47ef70b58?auto=format&fit=crop&w=900&q=80', 'credit' => 'Sanjay Jain', 'credit_url' => 'https://unsplash.com/@manojornaments__12'],
        ],
        'headwraps' => [
            ['url' => 'https://images.unsplash.com/photo-1770777352506-c36821bf7da8?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ben Iwara', 'credit_url' => 'https://unsplash.com/@1hundredimages'],
            ['url' => 'https://images.unsplash.com/photo-1667655966125-abae0f5823f7?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ato Aikins', 'credit_url' => 'https://unsplash.com/@ato_aikins'],
            ['url' => 'https://images.unsplash.com/photo-1590670796065-5c2469672e18?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ato Aikins', 'credit_url' => 'https://unsplash.com/@ato_aikins'],
            ['url' => 'https://images.unsplash.com/photo-1783038312854-f84ae9a0cc2f?auto=format&fit=crop&w=900&q=80', 'credit' => 'Babarinde Tosin', 'credit_url' => 'https://unsplash.com/@artistrypixels'],
            ['url' => 'https://images.unsplash.com/photo-1618998584360-10a0c28eec0f?auto=format&fit=crop&w=900&q=80', 'credit' => 'Dennis Irorere', 'credit_url' => 'https://unsplash.com/@denironyx'],
            ['url' => 'https://images.unsplash.com/photo-1681597107753-58e48bb38c32?auto=format&fit=crop&w=900&q=80', 'credit' => 'Divine Effiong', 'credit_url' => 'https://unsplash.com/@iamdivineeffiong'],
            ['url' => 'https://images.unsplash.com/photo-1613173992210-2a89df02d28c?auto=format&fit=crop&w=900&q=80', 'credit' => 'fame of God studios', 'credit_url' => 'https://unsplash.com/@fameofgodstudios_1'],
            ['url' => 'https://images.unsplash.com/photo-1591726265496-0bbd67e5c0e8?auto=format&fit=crop&w=900&q=80', 'credit' => 'Moa Király', 'credit_url' => 'https://unsplash.com/@moakb'],
            ['url' => 'https://images.unsplash.com/photo-1743871698163-a2e470d8eac7?auto=format&fit=crop&w=900&q=80', 'credit' => 'Angelo Casto', 'credit_url' => 'https://unsplash.com/@jddartphotographer'],
            ['url' => 'https://images.unsplash.com/photo-1613876215075-276fd62c89a4?auto=format&fit=crop&w=900&q=80', 'credit' => 'Gideon Hezekiah', 'credit_url' => 'https://unsplash.com/@gideonhezekiah'],
        ],
        'kids' => [
            ['url' => 'https://images.unsplash.com/photo-1604303768345-038b79a8c47a?auto=format&fit=crop&w=900&q=80', 'credit' => 'behrouz sasani', 'credit_url' => 'https://unsplash.com/@behrouzsasani'],
            ['url' => 'https://images.unsplash.com/photo-1604482858862-1db908a653e4?auto=format&fit=crop&w=900&q=80', 'credit' => 'behrouz sasani', 'credit_url' => 'https://unsplash.com/@behrouzsasani'],
            ['url' => 'https://images.unsplash.com/photo-1502451885777-16c98b07834a?auto=format&fit=crop&w=900&q=80', 'credit' => 'Janko Ferlič', 'credit_url' => 'https://unsplash.com/@itfeelslikefilm'],
            ['url' => 'https://images.unsplash.com/photo-1725147874578-fc76e0d865e1?auto=format&fit=crop&w=900&q=80', 'credit' => 'Faizi Sheikh', 'credit_url' => 'https://unsplash.com/@faizisheikh'],
            ['url' => 'https://images.unsplash.com/photo-1725147874926-dfebe6d996ef?auto=format&fit=crop&w=900&q=80', 'credit' => 'Faizi Sheikh', 'credit_url' => 'https://unsplash.com/@faizisheikh'],
            ['url' => 'https://images.unsplash.com/photo-1725147874938-7904e3362841?auto=format&fit=crop&w=900&q=80', 'credit' => 'Faizi Sheikh', 'credit_url' => 'https://unsplash.com/@faizisheikh'],
            ['url' => 'https://images.unsplash.com/photo-1758782213532-bbb5fd89885e?auto=format&fit=crop&w=900&q=80', 'credit' => 'Atiyeh Fathi', 'credit_url' => 'https://unsplash.com/@atiyehfathi'],
            ['url' => 'https://images.unsplash.com/photo-1632232963035-bc14755747c9?auto=format&fit=crop&w=900&q=80', 'credit' => 'The Artist Studio', 'credit_url' => 'https://unsplash.com/@theartiststidio'],
            ['url' => 'https://images.unsplash.com/photo-1566454544259-f4b94c3d758c?auto=format&fit=crop&w=900&q=80', 'credit' => 'Hafizha Anisa', 'credit_url' => 'https://unsplash.com/@anisa_flg'],
            ['url' => 'https://images.unsplash.com/photo-1560859259-fcf2b952aed8?auto=format&fit=crop&w=900&q=80', 'credit' => 'Baby Natur', 'credit_url' => 'https://unsplash.com/@babynatur'],
            ['url' => 'https://images.unsplash.com/photo-1741992556912-3b2d62461e75?auto=format&fit=crop&w=900&q=80', 'credit' => 'Zoshua Colah', 'credit_url' => 'https://unsplash.com/@zoshuacolah'],
            ['url' => 'https://images.unsplash.com/photo-1760287363879-6012adab292c?auto=format&fit=crop&w=900&q=80', 'credit' => 'Zayed Ahmed Zadu', 'credit_url' => 'https://unsplash.com/@zayed_ahmed_zadu'],
        ],
        'accessories' => [
            ['url' => 'https://images.unsplash.com/photo-1612902457652-33aff0a641fa?auto=format&fit=crop&w=900&q=80', 'credit' => 'Farah Samy', 'credit_url' => 'https://unsplash.com/@farahgsamy'],
            ['url' => 'https://images.unsplash.com/photo-1588768897961-332c50c55d18?auto=format&fit=crop&w=900&q=80', 'credit' => 'vitoandwilly', 'credit_url' => 'https://unsplash.com/@vitowilly'],
            ['url' => 'https://images.unsplash.com/photo-1618677366787-c02f9a52d6e8?auto=format&fit=crop&w=900&q=80', 'credit' => 'Colin Lloyd', 'credit_url' => 'https://unsplash.com/@onthesearchforpineapples'],
            ['url' => 'https://images.unsplash.com/photo-1641978020339-7fffd3bf4794?auto=format&fit=crop&w=900&q=80', 'credit' => 'HamZa NOUASRIA', 'credit_url' => 'https://unsplash.com/@hamza01nsr'],
            ['url' => 'https://images.unsplash.com/photo-1523754865311-b886113bb8de?auto=format&fit=crop&w=900&q=80', 'credit' => 'S O C I A L . C U T', 'credit_url' => 'https://unsplash.com/@socialcut'],
            ['url' => 'https://images.unsplash.com/photo-1537832816519-689ad163238b?auto=format&fit=crop&w=900&q=80', 'credit' => 'S O C I A L . C U T', 'credit_url' => 'https://unsplash.com/@socialcut'],
            ['url' => 'https://images.unsplash.com/photo-1525962493498-1b90aaf10483?auto=format&fit=crop&w=900&q=80', 'credit' => 'Sven', 'credit_url' => 'https://unsplash.com/@shauste'],
            ['url' => 'https://images.unsplash.com/photo-1718579631572-c2016d5dcd85?auto=format&fit=crop&w=900&q=80', 'credit' => 'Thayná Gandra', 'credit_url' => 'https://unsplash.com/@thaynagandra'],
            ['url' => 'https://images.unsplash.com/photo-1514867312438-db0960ee52e5?auto=format&fit=crop&w=900&q=80', 'credit' => 'Anika Huizinga', 'credit_url' => 'https://unsplash.com/@iam_anih'],
            ['url' => 'https://images.unsplash.com/photo-1767020990482-4cbdf3e7bdeb?auto=format&fit=crop&w=900&q=80', 'credit' => 'Brooke Balentine', 'credit_url' => 'https://unsplash.com/@brookebalentine'],
            ['url' => 'https://images.unsplash.com/photo-1607340218968-482147e8aa00?auto=format&fit=crop&w=900&q=80', 'credit' => 'Nasim Keshmiri', 'credit_url' => 'https://unsplash.com/@nasimkeshmiri'],
            ['url' => 'https://images.unsplash.com/photo-1483395869868-bb0a864c076f?auto=format&fit=crop&w=900&q=80', 'credit' => 'Toa Heftiba', 'credit_url' => 'https://unsplash.com/@heftiba'],
            ['url' => 'https://images.unsplash.com/photo-1683464582964-32cd7da7f4f9?auto=format&fit=crop&w=900&q=80', 'credit' => 'Grace Hilty', 'credit_url' => 'https://unsplash.com/@ghilty'],
            ['url' => 'https://images.unsplash.com/photo-1764593154804-e7646a005ce0?auto=format&fit=crop&w=900&q=80', 'credit' => 'Dion Martins', 'credit_url' => 'https://unsplash.com/@dionmartins'],
        ],
    ],

    // Editorial / fabric close-ups for banners and section backgrounds.
    'editorial' => [
        ['url' => 'https://images.unsplash.com/photo-1768212565426-58b089b6386d?auto=format&fit=crop&w=900&q=80', 'credit' => 'WyteShot', 'credit_url' => 'https://unsplash.com/@wyteshot'],
        ['url' => 'https://images.unsplash.com/photo-1768212566108-4ce4f329e4d2?auto=format&fit=crop&w=900&q=80', 'credit' => 'WyteShot', 'credit_url' => 'https://unsplash.com/@wyteshot'],
        ['url' => 'https://images.unsplash.com/photo-1772411535291-aa5884035934?auto=format&fit=crop&w=900&q=80', 'credit' => 'Youssef Mubarak', 'credit_url' => 'https://unsplash.com/@youssmub'],
        ['url' => 'https://images.unsplash.com/photo-1784123476705-f92b827da974?auto=format&fit=crop&w=900&q=80', 'credit' => 'Barney Goodman', 'credit_url' => 'https://unsplash.com/@bgoodpic'],
        ['url' => 'https://images.unsplash.com/photo-1784123476742-de48239d2fb0?auto=format&fit=crop&w=900&q=80', 'credit' => 'Barney Goodman', 'credit_url' => 'https://unsplash.com/@bgoodpic'],
        ['url' => 'https://images.unsplash.com/photo-1552710307-537199cd41c0?auto=format&fit=crop&w=900&q=80', 'credit' => 'Eva Blue', 'credit_url' => 'https://unsplash.com/@evablue'],
        ['url' => 'https://images.unsplash.com/photo-1578509566163-068acd11b8e7?auto=format&fit=crop&w=900&q=80', 'credit' => 'Patrick Mueller', 'credit_url' => 'https://unsplash.com/@pietyo'],
        ['url' => 'https://images.unsplash.com/photo-1734868032501-448d1457dc38?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ingeborg Korme', 'credit_url' => 'https://unsplash.com/@ingeborgkorme'],
        ['url' => 'https://images.unsplash.com/photo-1760727467662-5f0943d196a8?auto=format&fit=crop&w=900&q=80', 'credit' => 'mathieu gauzy', 'credit_url' => 'https://unsplash.com/@gozstudio'],
        ['url' => 'https://images.unsplash.com/photo-1769717476866-ac3717d1b314?auto=format&fit=crop&w=900&q=80', 'credit' => 'Zoltan Fekeshazy', 'credit_url' => 'https://unsplash.com/@fekeshazizo'],
        ['url' => 'https://images.unsplash.com/photo-1694062045776-f48d9b6de57e?auto=format&fit=crop&w=900&q=80', 'credit' => 'Ardy Arjun', 'credit_url' => 'https://unsplash.com/@ardyastic'],
        ['url' => 'https://images.unsplash.com/photo-1601387603639-387c75bdcb0d?auto=format&fit=crop&w=900&q=80', 'credit' => 'Eyasu Etsub', 'credit_url' => 'https://unsplash.com/@jphotography2012'],
        ['url' => 'https://images.unsplash.com/photo-1574362098421-38623a3466b5?auto=format&fit=crop&w=900&q=80', 'credit' => 'Bazil Julius', 'credit_url' => 'https://unsplash.com/@wibazil'],
        ['url' => 'https://images.unsplash.com/photo-1766107349403-673a73ad5cb3?auto=format&fit=crop&w=900&q=80', 'credit' => 'Stephen Tettey Atsu', 'credit_url' => 'https://unsplash.com/@atsu_tettey'],
        ['url' => 'https://images.unsplash.com/photo-1578353022142-09264fd64295?auto=format&fit=crop&w=900&q=80', 'credit' => 'Darling Arias', 'credit_url' => 'https://unsplash.com/@darlingarias'],
        ['url' => 'https://images.unsplash.com/photo-1739117441029-9f2a8e59e8b2?auto=format&fit=crop&w=900&q=80', 'credit' => 'Khang Nguyen', 'credit_url' => 'https://unsplash.com/@kivamyth'],
        ['url' => 'https://images.unsplash.com/photo-1673201229733-69d19c5c4a87?auto=format&fit=crop&w=900&q=80', 'credit' => 'Collab Media', 'credit_url' => 'https://unsplash.com/@collab_media'],
    ],
];
