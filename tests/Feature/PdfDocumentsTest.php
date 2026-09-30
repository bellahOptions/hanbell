<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\VendorStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Commerce\CartService;
use App\Services\Commerce\OrderService;
use App\Services\Pdf\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The PDF document suite.
 *
 * These tests read the generated PDFs back as text rather than only checking the
 * status code. A 200 with a valid-looking body can still be a document that
 * renders every Nigerian brand name as a row of tofu boxes, or one that prints
 * escaped HTML because a template emitted its slot with {{ }} instead of {!! !!}
 * — both of which happened during development and neither of which a status
 * assertion would have caught.
 */
class PdfDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seedFixture();

        auth()->login($this->customer);

        $cart = app(CartService::class);
        $cart->clear();

        $variant = $this->inStockVariant();

        $cart->add($variant->product, $variant, 1);

        $this->order = app(OrderService::class)->createFromCart(
            cart: $cart->current(),
            shipping: [
                // Deliberately a name with combining diacritics: if PDF font
                // handling regresses, this is what catches it.
                'recipient_name' => 'Adaeze Ọ̀rẹ́ Okonkwo',
                'phone' => '+234 803 000 0000',
                'line1' => '14 Adeola Odeku Street',
                'city' => 'Victoria Island',
                'state' => 'Lagos',
                'country' => 'NG',
            ],
            contact: [
                'name' => 'Adaeze Ọ̀rẹ́ Okonkwo',
                'email' => $this->customer->email,
                'phone' => '+234 803 000 0000',
            ],
            user: $this->customer,
        );

        $this->order = app(OrderService::class)->markPaid($this->order);
    }

    /* ------------------------------------------------------------------ *
     * Rendering
     * ------------------------------------------------------------------ */

    #[Test]
    public function the_invoice_renders_as_a_real_pdf(): void
    {
        $response = $this->get(route('documents.invoice', ['order' => $this->order->urlToken()]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $bytes = $response->getContent();
        $this->assertStringStartsWith('%PDF', $bytes, 'The response is not a PDF.');
    }

    #[Test]
    public function every_document_in_the_suite_renders(): void
    {
        $documents = app(DocumentService::class);

        $rendered = [
            'invoice' => $documents->invoiceData($this->order),
            'receipt' => $documents->receiptData($this->order),
            'packing-slip' => $documents->packingSlipData($this->order),
            'terms' => $documents->termsData(),
        ];

        $renderer = app(\App\Services\Pdf\PdfRenderer::class);

        foreach ($rendered as $name => $data) {
            $bytes = $renderer->render('pdf.'.$name, $data);

            $this->assertStringStartsWith('%PDF', $bytes, "The {$name} did not render as a PDF.");
            $this->assertGreaterThan(5_000, strlen($bytes), "The {$name} looks suspiciously small.");
        }
    }

    /* ------------------------------------------------------------------ *
     * Content correctness
     * ------------------------------------------------------------------ */

    #[Test]
    public function the_invoice_names_the_operating_entity_and_renders_naira(): void
    {
        $text = $this->pdfText('pdf.invoice', app(DocumentService::class)->invoiceData($this->order));

        $this->assertStringContainsString(
            (string) config('hanbell.operator.name'),
            $text,
            'The invoice must name the entity behind the brand.',
        );

        $this->assertStringContainsString("\u{20A6}", $text, 'The Naira sign did not render.');
        $this->assertStringNotContainsString("\u{FFFD}", $text, 'The invoice contains replacement characters.');
    }

    #[Test]
    public function nigerian_brand_names_survive_pdf_generation(): void
    {
        // Domain names in the seed use precomposed Yoruba and Igbo characters.
        // If the font stack regresses these become tofu, so assert on one.
        $text = $this->pdfText('pdf.invoice', app(DocumentService::class)->invoiceData($this->order));

        $hasDiacritic = str_contains($text, "\u{1ECC}")   // Ọ
            || str_contains($text, "\u{1EB9}")            // ẹ
            || str_contains($text, "\u{1ECD}")            // ọ
            || str_contains($text, "\u{1ECB}");           // ị

        $this->assertTrue(
            $hasDiacritic,
            'Nigerian diacritics were lost — the PDF font cannot render them.',
        );
    }

    #[Test]
    public function the_packing_slip_does_not_leak_prices(): void
    {
        // It travels inside the parcel and is often handled by a courier or left
        // with a neighbour, so it must not reveal what the customer paid.
        $text = $this->pdfText('pdf.packing-slip', app(DocumentService::class)->packingSlipData($this->order));

        $this->assertStringNotContainsString("\u{20A6}", $text, 'The packing slip printed a price.');
        $this->assertStringContainsString('PACKING SLIP', strtoupper($text));
    }

    #[Test]
    public function the_terms_document_states_the_governing_law_and_the_entity(): void
    {
        $text = $this->pdfText('pdf.terms', app(DocumentService::class)->termsData());

        $this->assertStringContainsString((string) config('hanbell.operator.name'), $text);
        $this->assertStringContainsString('Terms of Service', $text);

        // The jurisdiction clause is added by the document, not the web page.
        $this->assertMatchesRegularExpression('/governing law|jurisdiction/i', $text);
    }

    #[Test]
    public function documents_do_not_print_escaped_html_markup(): void
    {
        // A template that emits its body with {{ }} instead of {!! !!} produces a
        // document full of visible tags. This is the regression guard for that.
        foreach (['invoice', 'receipt', 'credit-note', 'packing-slip'] as $name) {
            $data = match ($name) {
                'invoice' => app(DocumentService::class)->invoiceData($this->order),
                'receipt' => app(DocumentService::class)->receiptData($this->order),
                'credit-note' => app(DocumentService::class)->creditNoteData($this->order, 'Test'),
                'packing-slip' => app(DocumentService::class)->packingSlipData($this->order),
            };

            $text = $this->pdfText('pdf.'.$name, $data);

            $this->assertStringNotContainsString('<td', $text, "The {$name} printed raw markup.");
            $this->assertStringNotContainsString('<div', $text, "The {$name} printed raw markup.");
            $this->assertStringNotContainsString('class="', $text, "The {$name} printed raw markup.");
        }
    }

    /* ------------------------------------------------------------------ *
     * Availability by order state
     * ------------------------------------------------------------------ */

    #[Test]
    public function an_invoice_is_not_available_for_an_unpaid_order(): void
    {
        // An invoice is a demand for payment; issuing one for an unpaid order
        // invites a payment we would then have to refund.
        $unpaid = Order::create([
            'number' => 'HB-2026-999999',
            'user_id' => $this->customer->id,
            'email' => $this->customer->email,
            'customer_name' => $this->customer->name,
            'shipping_recipient_name' => 'Test',
            'shipping_phone' => '+2348000000000',
            'shipping_line1' => '1 Test Street',
            'shipping_city' => 'Lagos',
            'shipping_state' => 'Lagos',
            'currency' => 'NGN',
            'subtotal_minor' => 1000,
            'total_minor' => 1000,
            'status' => OrderStatus::Pending,
        ]);

        $this->get(route('documents.invoice', ['order' => $unpaid->urlToken()]))->assertNotFound();
        $this->get(route('documents.receipt', ['order' => $unpaid->urlToken()]))->assertNotFound();
    }

    #[Test]
    public function a_credit_note_is_not_available_until_something_is_refunded(): void
    {
        $this->get(route('documents.credit-note', ['order' => $this->order->urlToken()]))
            ->assertNotFound();
    }

    #[Test]
    public function a_packing_slip_is_available_as_soon_as_the_order_exists(): void
    {
        // It is what travels in the parcel, so it does not wait for payment.
        $this->get(route('documents.packing-slip', ['order' => $this->order->urlToken()]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /* ------------------------------------------------------------------ *
     * Authorisation
     * ------------------------------------------------------------------ */

    #[Test]
    public function another_customer_cannot_download_someone_elses_invoice(): void
    {
        $other = User::create([
            'name' => 'Someone Else',
            'email' => 'other@hanbellshop.demo',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $other->assignRole('customer');

        $this->actingAs($other)
            ->get(route('documents.invoice', ['order' => $this->order->urlToken()]))
            ->assertNotFound();
    }

    #[Test]
    public function a_tampered_order_token_is_refused(): void
    {
        $token = $this->order->urlToken();
        $tampered = substr($token, 0, -4).'AAAA';

        $this->get(route('documents.invoice', ['order' => $tampered]))->assertNotFound();
    }

    #[Test]
    public function the_terms_document_is_publicly_available(): void
    {
        // Published material: a customer must be able to read it before buying.
        auth()->logout();

        $this->get(route('documents.terms'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /* ------------------------------------------------------------------ *
     * Vendor statements
     * ------------------------------------------------------------------ */

    #[Test]
    public function a_vendor_can_download_their_own_statement_but_not_another_brands(): void
    {
        $vendorOrders = $this->order->vendorOrders()->with('vendor')->get();

        $this->assertNotEmpty($vendorOrders, 'The order should have at least one vendor sub-order.');

        $mine = $vendorOrders->first();
        $owner = $mine->vendor?->owner;

        if ($owner === null) {
            $this->markTestSkipped('The seeded vendor has no owner account.');
        }

        // Their own statement renders.
        $this->actingAs($owner)
            ->get(route('documents.vendor-statement', [
                'order' => $this->order->urlToken(),
                'vendorOrder' => $mine->id,
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // A statement belonging to a different brand does not.
        $other = $vendorOrders->firstWhere('id', '!=', $mine->id);

        if ($other !== null) {
            $this->actingAs($owner)
                ->get(route('documents.vendor-statement', [
                    'order' => $this->order->urlToken(),
                    'vendorOrder' => $other->id,
                ]))
                ->assertNotFound();
        }
    }

    #[Test]
    public function the_vendor_statement_shows_commission_and_net_payable(): void
    {
        $vendorOrder = $this->order->vendorOrders()->with('vendor')->firstOrFail();

        $text = $this->pdfText('pdf.vendor-statement', [
            'documentTitle' => 'Vendor statement',
            'documentType' => 'Statement',
            'documentReference' => 'STMT-TEST',
            'documentStatus' => 'Paid',
            'statementNumber' => 'STMT-TEST',
            'vendorOrder' => $vendorOrder,
            'order' => $this->order->load('items'),
            'recipient' => new \App\Services\Pdf\Data\DocumentParty(name: 'Test Brand'),
            'lines' => app(\App\Services\Pdf\DocumentFactory::class)->linesForVendor($this->order, $vendorOrder),
            'totals' => app(\App\Services\Pdf\DocumentFactory::class)->totalsForVendor($this->order, $vendorOrder),
            'issueDate' => now(),
        ]);

        $this->assertStringContainsString('Commission', $text);
        $this->assertStringContainsString('Net payable', $text);
    }

    /* ------------------------------------------------------------------ *
     * Numbering
     * ------------------------------------------------------------------ */

    #[Test]
    public function document_numbers_are_derived_from_the_order_and_reproducible(): void
    {
        $documents = app(DocumentService::class);

        // Derived, not counted: re-downloading a six-month-old invoice must give
        // the identical document rather than one that depends on how many
        // invoices happened to be issued before it.
        $this->assertSame('INV-'.$this->order->number, $documents->invoiceNumber($this->order));
        $this->assertSame('RCP-'.$this->order->number, $documents->receiptNumber($this->order));
        $this->assertSame('CRN-'.$this->order->number, $documents->creditNoteNumber($this->order));

        $this->assertSame(
            $documents->invoiceNumber($this->order),
            $documents->invoiceNumber($this->order->fresh()),
        );
    }

    #[Test]
    public function the_response_is_not_cacheable(): void
    {
        // Financial documents must not sit in a shared proxy.
        $response = $this->get(route('documents.invoice', ['order' => $this->order->urlToken()]));

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    /* ------------------------------------------------------------------ *
     * Helpers
     * ------------------------------------------------------------------ */

    /**
     * Render a document and read it back as text.
     *
     * @param  array<string,mixed>  $data
     */
    private function pdfText(string $view, array $data): string
    {
        $renderer = app(\App\Services\Pdf\PdfRenderer::class);

        $paper = $view === 'pdf.receipt' ? 'a5' : null;
        $bytes = $renderer->render($view, $data, $paper);

        $path = tempnam(sys_get_temp_dir(), 'hbpdf').'.pdf';
        file_put_contents($path, $bytes);

        try {
            $parser = new \Smalot\PdfParser\Parser;
            $text = $parser->parseFile($path)->getText();
        } finally {
            @unlink($path);
        }

        return $text;
    }

    private function inStockVariant(): \App\Models\ProductVariant
    {
        $variant = \App\Models\ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('inventory', fn ($q) => $q->whereRaw('quantity_on_hand - quantity_reserved > 2'))
            ->whereHas('product', fn ($q) => $q->where('status', 'published'))
            ->with('product')
            ->first();

        if ($variant === null) {
            $this->fail('The fixture has no in-stock variant.');
        }

        return $variant;
    }

    /**
     * A minimal catalogue plus the accounts the test needs.
     */
    private function seedFixture(): void
    {
        $this->customer = User::create([
            'name' => 'Adaeze Okonkwo',
            'email' => 'customer@hanbellshop.demo',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        $this->customer->assignRole('customer');

        $department = \App\Models\Department::create([
            'name' => 'Women', 'gender' => 'women', 'is_active' => true,
        ]);

        $category = \App\Models\Category::create([
            'department_id' => $department->id, 'name' => 'Dresses', 'is_active' => true,
        ]);

        // Two brands, so a multi-vendor order can be exercised.
        foreach (['Ọ̀rẹ́ Studio', 'Ndị Aba Shoemakers'] as $index => $brandName) {
            $owner = User::create([
                'name' => $brandName.' owner',
                'email' => 'owner'.$index.'@hanbellshop.demo',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            $owner->assignRole('vendor');

            $vendor = Vendor::create([
                'owner_id' => $owner->id,
                'name' => $brandName,
                'city' => $index === 0 ? 'Enugu' : 'Aba',
                'state' => $index === 0 ? 'Enugu' : 'Abia',
                'description' => 'A test brand.',
                'status' => VendorStatus::Approved,
                'approved_at' => now(),
            ]);

            $product = Product::create([
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'department_id' => $department->id,
                'name' => $index === 0 ? 'Adire Wrap Dress' : 'Hand-Stitched Loafers',
                'summary' => 'A test product.',
                'price_minor' => 4_500_000,
                'currency' => 'NGN',
                'status' => \App\Enums\ProductStatus::Published,
                'published_at' => now(),
                'gender' => 'women',
                'made_in_city' => $vendor->city,
            ]);

            $variant = \App\Models\ProductVariant::create([
                'product_id' => $product->id,
                'name' => 'M / Indigo',
                'sku' => 'SKU-'.strtoupper(substr(md5($brandName), 0, 8)),
                'size' => 'M',
                'color' => 'Indigo',
                'is_active' => true,
            ]);

            \App\Models\Inventory::create([
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 20,
                'quantity_reserved' => 0,
            ]);

            \App\Models\Inventory::create([
                'product_id' => $product->id,
                'variant_id' => null,
                'quantity_on_hand' => 20,
                'quantity_reserved' => 0,
            ]);
        }

        \App\Models\Page::create([
            'title' => 'Terms of Service',
            'slug' => 'terms-of-service',
            'content' => '<p>These terms govern your use of HanbellShop.</p><h2>About these terms</h2><p>HanbellShop is a marketplace.</p><h2>Accounts</h2><p>You need an account to check out.</p>',
            'group' => 'policy',
            'status' => \App\Enums\PageStatus::Published,
            'published_at' => now(),
            'show_in_footer' => true,
        ]);
    }
}
