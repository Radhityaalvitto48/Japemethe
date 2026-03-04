import { useState, useEffect, useRef, useCallback } from 'react';
import { Carousel, CarouselSlide } from '@/components/ui/Carousel';

interface Banner {
    id: number;
    image: string;
    title?: string;
}

interface PromoSectionProps {
    banners?: Banner[];
}

interface CarouselResponse {
    success: boolean;
    data: Banner[];
}

const defaultBanners: Banner[] = [
    { id: 1, image: '/images/banners/banner-1.jpg', title: 'Banner 1' },
    { id: 2, image: '/images/banners/banner-2.jpg', title: 'Banner 2' },
    { id: 3, image: '/images/banners/banner-3.jpg', title: 'Banner 3' },
];

export default function PromoSection({ banners }: PromoSectionProps) {
    const [carouselBanners, setCarouselBanners] = useState<Banner[]>(defaultBanners);
    const [loading, setLoading] = useState(true);
    const [imagesLoaded, setImagesLoaded] = useState<Set<number>>(new Set());
    const preloadedRef = useRef<Set<string>>(new Set());
    const abortController = useRef<AbortController | null>(null);

    // Preload gambar untuk optimasi LCP dengan cleanup
    const preloadImage = useCallback((src: string) => {
        if (preloadedRef.current.has(src) || !src) return;

        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'image';
        link.href = src;
        link.fetchPriority = 'high';
        document.head.appendChild(link);
        preloadedRef.current.add(src);

        // Cleanup setelah 10 detik
        setTimeout(() => {
            if (document.head.contains(link)) {
                document.head.removeChild(link);
            }
        }, 10000);
    }, []);

    // Prefetch gambar berikutnya dengan debounce
    const prefetchImage = useCallback((src: string) => {
        if (preloadedRef.current.has(src) || !src) return;

        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.as = 'image';
        link.href = src;
        document.head.appendChild(link);
        preloadedRef.current.add(src);
    }, []);

    useEffect(() => {
        if (abortController.current) {
            abortController.current.abort();
        }

        abortController.current = new AbortController();

        const fetchCarousels = async () => {
            try {
                const response = await fetch('/api/carousels/active', {
                    signal: abortController.current?.signal,
                    headers: {
                        'Accept': 'application/json',
                        'Cache-Control': 'max-age=300'
                    }
                });

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const result: CarouselResponse = await response.json();

                if (result.success && result.data.length > 0) {
                    setCarouselBanners(result.data);

                    if (result.data[0]?.image) {
                        preloadImage(result.data[0].image);

                        result.data.slice(1, 3).forEach((banner, index) => {
                            if (banner.image) {
                                setTimeout(() => prefetchImage(banner.image), (index + 1) * 50);
                            }
                        });
                    }
                } else {
                    setCarouselBanners(defaultBanners);
                    preloadImage(defaultBanners[0].image);
                }
            } catch (error) {
                if (error instanceof Error && error.name === 'AbortError') {
                    return;
                }
                console.error('Error fetching carousels:', error);
                setCarouselBanners(defaultBanners);
                preloadImage(defaultBanners[0].image);
            } finally {
                setLoading(false);
            }
        };

        if (banners && banners.length > 0) {
            setCarouselBanners(banners);
            if (banners[0]?.image) {
                preloadImage(banners[0].image);
            }
            setLoading(false);
        } else {
            fetchCarousels();
        }

        return () => {
            if (abortController.current) {
                abortController.current.abort();
            }
        };
    }, [banners, preloadImage, prefetchImage]);

    const items = carouselBanners;

    const handleImageLoaded = useCallback((bannerId: number) => {
        setImagesLoaded(prev => new Set(prev).add(bannerId));
    }, []);

    if (loading) {
        return (
            <section className="px-4 pt-4">
                <div
                    className="aspect-[16/7] w-full flex items-center justify-center rounded-xl bg-gradient-to-r from-gray-200 to-gray-300"
                    style={{ containIntrinsicSize: '1 auto' }}
                >
                    <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                    <div className="text-center text-gray-400 p-4 relative z-10">
                        <p className="text-xl font-bold">Loading...</p>
                    </div>
                </div>
            </section>
        );
    }

    return (
        <section className="px-4 pt-4">
            {/* Performance hints */}
            {items.length > 0 && (
                <>
                    <link rel="dns-prefetch" href={new URL(items[0].image).origin} />
                    <link rel="preconnect" href={new URL(items[0].image).origin} crossOrigin="anonymous" />
                </>
            )}

            <div className="carousel-container" style={{ containIntrinsicSize: '1200px 675px' }}>
                <Carousel
                    autoplay
                    autoplayInterval={4000}
                    showArrows={false}
                    showDots
                    className="transform-gpu" // Hardware acceleration
                >
                    {items.map((banner, index) => (
                        <CarouselSlide key={banner.id}>
                            <BannerImage
                                banner={banner}
                                isPriority={index === 0}
                                onLoad={() => handleImageLoaded(banner.id)}
                                isLoaded={imagesLoaded.has(banner.id)}
                            />
                        </CarouselSlide>
                    ))}
                </Carousel>
            </div>
        </section>
    );
}

function BannerImage({
    banner,
    isPriority = false,
    onLoad,
    isLoaded = false
}: {
    banner: Banner;
    isPriority?: boolean;
    onLoad?: () => void;
    isLoaded?: boolean;
}) {
    const [error, setError] = useState(false);
    const [imageLoaded, setImageLoaded] = useState(false);
    const imgRef = useRef<HTMLImageElement>(null);

    const handleImageLoad = useCallback(() => {
        setImageLoaded(true);
        onLoad?.();
    }, [onLoad]);

    const handleImageError = useCallback(() => {
        setError(true);
    }, []);

    // Performance hint untuk browser
    useEffect(() => {
        if (isPriority && imgRef.current && 'decoding' in imgRef.current) {
            imgRef.current.decoding = 'sync';
        }
    }, [isPriority]);

    if (error) {
        return (
            <div className="aspect-[16/7] w-full flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-400 to-orange-500">
                <div className="text-center text-white p-4">
                    <p className="text-xl font-bold">JAPEMETHE</p>
                    <p className="text-sm mt-1 opacity-90">{banner.title || 'Promo Spesial'}</p>
                </div>
            </div>
        );
    }

    return (
        <div
            className={`aspect-[16/7] w-full overflow-hidden rounded-xl bg-gray-100 relative ${
                isPriority ? 'priority-image' : ''
            }`}
            style={isPriority ? {
                containIntrinsicSize: '1200px 675px',
                contentVisibility: 'visible'
            } : {
                contentVisibility: 'auto'
            }}
        >
            {/* Loading skeleton - optimized */}
            {!imageLoaded && (
                <div className="absolute inset-0 bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200">
                    <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer"
                         style={{ animationDuration: '1.2s' }}>
                    </div>
                </div>
            )}

            <img
                ref={imgRef}
                src={banner.image}
                alt={banner.title || 'Promo Banner'}
                className={`h-full w-full object-cover transition-opacity duration-200 ${
                    imageLoaded ? 'opacity-100 scale-100' : 'opacity-0 scale-105'
                } ${imageLoaded ? 'image-fade-in' : ''}`}
                loading={isPriority ? 'eager' : 'lazy'}
                fetchPriority={isPriority ? 'high' : 'low'}
                decoding={isPriority ? 'sync' : 'async'}
                onLoad={handleImageLoad}
                onError={handleImageError}
                // Responsive sizing hints
                sizes={isPriority ? '100vw' : '(max-width: 768px) 100vw, 1200px'}
                // ARIA untuk accessibility
                role="img"
                aria-label={banner.title || 'Promotional banner'}
                style={isPriority ? {
                    willChange: 'auto',
                    transform: 'translateZ(0)' // Force GPU layer
                } : undefined}
            />
        </div>
    );
}
