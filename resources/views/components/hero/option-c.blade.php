{{-- HERO OPTION C — "Red Threshold" (red/target) | 1920x720 | Safe zone: nội dung trong max-w-7xl trung tâm
     $compact = true → bản gọn cho đầu trang listing (giữ CTA + countdown ý tưởng, bỏ visual lớn) --}}
@php($compact = $compact ?? false)
<section class="relative overflow-hidden bg-bg-primary" aria-label="Đợt gom hàng nổi bật">
    <div class="absolute inset-0 bg-gradient-to-br from-bg-primary via-bg-secondary to-bg-primary" aria-hidden="true"></div>
    @unless($compact)
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none" aria-hidden="true">
        <div class="w-[640px] h-[640px] rounded-full border border-accent-red/20"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[440px] h-[440px] rounded-full border border-accent-red/30"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[240px] h-[240px] rounded-full" style="background: radial-gradient(circle, rgba(229,57,53,0.18) 0%, transparent 70%); filter: blur(50px);"></div>
    </div>
    @endunless
    <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-accent-red/40 to-transparent" aria-hidden="true"></div>
    <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-accent-red/40 to-transparent" aria-hidden="true"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 {{ $compact ? 'py-10' : 'py-16 lg:py-24 min-h-[480px] lg:min-h-[720px]' }} flex items-center">
        <div class="grid {{ $compact ? '' : 'lg:grid-cols-2' }} gap-12 items-center w-full">
            <div class="space-y-{{ $compact ? '4' : '6' }}">
                <p class="inline-flex items-center gap-2 px-4 py-2 bg-accent-red/10 border border-accent-red/30 rounded-full">
                    <span class="w-2 h-2 bg-accent-red rounded-full animate-pulse" aria-hidden="true"></span>
                    <span class="text-accent-red text-sm font-medium tracking-wide">SĂN HÀNG HIẾM THEO NGƯỠNG</span>
                </p>
                <h1 class="font-display text-white leading-none tracking-wider" style="font-size: clamp({{ $compact ? '2rem, 4vw, 3rem' : '2.5rem, 6vw, 5rem' }});">
                    NGẮM ĐÚNG NGƯỠNG<br><span class="text-accent-red">CHỐT ĐÚNG GUNDAM</span>
                </h1>
                @unless($compact)
                <p class="text-text-secondary text-base lg:text-lg max-w-xl">Mỗi slot của bạn kéo đợt gom gần hơn tới vạch đích. Không đủ ngưỡng — hệ thống tự hoàn cọc.</p>
                @endunless
                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('batches.index') }}" class="btn-outline-red px-8 py-4 text-base font-semibold tracking-wide !border-2">NHẮM ĐỢT GOM NGAY</a>
                    @unless($compact)
                    <a href="{{ route('products.index') }}" class="text-text-secondary hover:text-white text-sm font-medium transition-colors">Xem sản phẩm →</a>
                    @endunless
                </div>
            </div>
            @unless($compact)
            <div class="hidden lg:flex justify-center" aria-hidden="true">
                <div class="text-center">
                    <p class="text-text-secondary text-xs tracking-widest mb-2">VẠCH NGƯỠNG</p>
                    <p class="font-display text-white" style="font-size: 5rem; line-height: 1;">8<span class="text-accent-red" style="font-size: 2.5rem;">/10</span></p>
                    <p class="text-text-secondary text-sm mt-2 font-mono">SLOT ĐÃ GIỮ</p>
                </div>
            </div>
            @endunless
        </div>
    </div>
</section>
