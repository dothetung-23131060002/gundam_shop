{{-- HERO OPTION A — "Neon Assault" (blue) | 1920x720 | Safe zone: nội dung trong max-w-7xl trung tâm --}}
<section class="relative overflow-hidden bg-bg-primary" aria-label="Đợt gom hàng nổi bật">
    <div class="absolute inset-0 bg-gradient-to-br from-bg-primary via-bg-secondary to-bg-primary" aria-hidden="true"></div>
    <div class="absolute -top-32 -left-32 w-[500px] h-[500px] rounded-full pointer-events-none" style="background: radial-gradient(circle, var(--color-glow-blue) 0%, transparent 70%); filter: blur(60px);" aria-hidden="true"></div>
    <div class="absolute inset-0 opacity-[0.07] pointer-events-none" aria-hidden="true"
         style="background-image: linear-gradient(var(--color-accent-blue) 1px, transparent 1px), linear-gradient(90deg, var(--color-accent-blue) 1px, transparent 1px); background-size: 48px 48px;"></div>
    <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-accent-blue/40 to-transparent" aria-hidden="true"></div>
    <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-accent-blue/40 to-transparent" aria-hidden="true"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24 min-h-[480px] lg:min-h-[720px] flex items-center">
        <div class="grid lg:grid-cols-2 gap-12 items-center w-full">
            <div class="space-y-6">
                <p class="inline-flex items-center gap-2 px-4 py-2 bg-accent-blue/10 border border-accent-blue/30 rounded-full">
                    <span class="w-2 h-2 bg-accent-blue rounded-full animate-pulse" aria-hidden="true"></span>
                    <span class="text-accent-blue text-sm font-medium tracking-wide">GROUP PRE-ORDER • CHÍNH HÃNG BANDAI</span>
                </p>
                <h1 class="font-display text-white leading-none tracking-wider" style="font-size: clamp(2.5rem, 6vw, 5rem);">
                    GOM ĐỦ SLOT<br>
                    <span class="text-accent-blue">RINH GUNDAM</span> GIÁ GỐC
                </h1>
                <p class="text-text-secondary text-base lg:text-lg max-w-xl">Đặt cọc giữ slot theo đợt. Đủ ngưỡng là có hàng — thiếu ngưỡng hoàn cọc tự động, không rủi ro.</p>
                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('batches.index') }}" class="btn-primary px-8 py-4 text-base font-semibold tracking-wide">XEM ĐỢT GOM NGAY</a>
                    <a href="{{ route('products.index') }}" class="text-text-secondary hover:text-white text-sm font-medium transition-colors">Xem sản phẩm →</a>
                </div>
            </div>
            <div class="hidden lg:block" aria-hidden="true">
                <div class="mecha-border bg-bg-secondary/80 border border-border rounded-2xl p-8">
                    <p class="text-text-secondary text-xs tracking-widest mb-2">TIẾN ĐỘ MẪU</p>
                    <p class="font-display text-white" style="font-size: 4rem; line-height: 1;">8<span class="text-text-secondary" style="font-size: 2rem;">/10</span></p>
                    <div class="w-full h-3 bg-bg-primary rounded-full overflow-hidden mt-4">
                        <div class="h-full rounded-full bg-accent-blue" style="width: 80%"></div>
                    </div>
                    <p class="text-accent-blue font-mono text-sm mt-3">80% • còn 2 slot</p>
                </div>
            </div>
        </div>
    </div>
</section>
