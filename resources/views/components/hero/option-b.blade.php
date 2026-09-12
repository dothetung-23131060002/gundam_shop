{{-- HERO OPTION B — "Gold Rush" (gold/countdown) | 1920x720 | Safe zone: nội dung trong max-w-7xl trung tâm --}}
<section class="relative overflow-hidden bg-bg-primary" aria-label="Đợt gom hàng nổi bật">
    <div class="absolute inset-0 bg-gradient-to-bl from-bg-primary via-bg-secondary to-bg-primary" aria-hidden="true"></div>
    <div class="absolute top-1/2 right-[10%] -translate-y-1/2 w-[560px] h-[560px] rounded-full pointer-events-none" style="background: radial-gradient(circle, rgba(255,213,79,0.16) 0%, transparent 70%); filter: blur(70px);" aria-hidden="true"></div>
    <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-accent-gold/40 to-transparent" aria-hidden="true"></div>
    <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-accent-gold/40 to-transparent" aria-hidden="true"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24 min-h-[480px] lg:min-h-[720px] flex items-center">
        <div class="w-full text-center space-y-6">
            <p class="inline-flex items-center gap-2 px-4 py-2 bg-accent-gold/10 border border-accent-gold/30 rounded-full">
                <span class="w-2 h-2 bg-accent-gold rounded-full animate-pulse" aria-hidden="true"></span>
                <span class="text-accent-gold text-sm font-medium tracking-wide">CỌC NHỎ • GIÁ ƯU ĐÃI THEO ĐỢT</span>
            </p>
            <h1 class="font-display text-white leading-none tracking-wider mx-auto" style="font-size: clamp(2.5rem, 6vw, 5rem);">
                ĐẶT CỌC GIỮ SLOT<br>ĐỦ NGƯỠNG <span class="text-accent-gold">LÀ CÓ HÀNG</span>
            </h1>
            <div class="flex flex-wrap items-center justify-center gap-3" role="timer" aria-label="Ví dụ đếm ngược đợt gom">
                @foreach([['07','NGÀY'],['12','GIỜ'],['45','PHÚT']] as $box)
                    <div class="bg-bg-secondary border border-accent-gold/30 rounded-xl px-4 py-3 min-w-[76px]">
                        <p class="font-display text-accent-gold" style="font-size: 2rem; line-height: 1;">{{ $box[0] }}</p>
                        <p class="text-text-secondary text-[10px] tracking-widest mt-1">{{ $box[1] }}</p>
                    </div>
                @endforeach
            </div>
            <p class="text-text-secondary text-base lg:text-lg max-w-xl mx-auto">Mỗi đợt có deadline. Nhanh tay giữ slot trước khi đồng hồ về 0.</p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('batches.index') }}" class="px-8 py-4 text-base font-semibold tracking-wide rounded-xl transition-all" style="background: var(--color-accent-gold); color: #0A0A0F;">GIỮ SLOT NGAY</a>
                <a href="{{ route('products.index') }}" class="text-text-secondary hover:text-white text-sm font-medium transition-colors">Xem sản phẩm →</a>
            </div>
        </div>
    </div>
</section>
