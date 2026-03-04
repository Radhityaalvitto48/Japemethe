import { cn } from '@/lib/utils';
import useEmblaCarousel from 'embla-carousel-react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { HTMLAttributes, forwardRef, useCallback, useEffect, useState } from 'react';

interface CarouselProps extends HTMLAttributes<HTMLDivElement> {
    autoplay?: boolean;
    autoplayInterval?: number;
    showArrows?: boolean;
    showDots?: boolean;
}

const Carousel = forwardRef<HTMLDivElement, CarouselProps>(
    ({ className, children, autoplay = false, autoplayInterval = 3000, showArrows = true, showDots = true, ...props }, ref) => {
        const [emblaRef, emblaApi] = useEmblaCarousel({ loop: true });
        const [selectedIndex, setSelectedIndex] = useState(0);
        const [scrollSnaps, setScrollSnaps] = useState<number[]>([]);

        const scrollPrev = useCallback(() => {
            if (emblaApi) emblaApi.scrollPrev();
        }, [emblaApi]);

        const scrollNext = useCallback(() => {
            if (emblaApi) emblaApi.scrollNext();
        }, [emblaApi]);

        const scrollTo = useCallback(
            (index: number) => {
                if (emblaApi) emblaApi.scrollTo(index);
            },
            [emblaApi],
        );

        const onSelect = useCallback(() => {
            if (!emblaApi) return;
            setSelectedIndex(emblaApi.selectedScrollSnap());
        }, [emblaApi]);

        useEffect(() => {
            if (!emblaApi) return;
            onSelect();
            setScrollSnaps(emblaApi.scrollSnapList());
            emblaApi.on('select', onSelect);
            return () => {
                emblaApi.off('select', onSelect);
            };
        }, [emblaApi, onSelect]);

        // Autoplay
        useEffect(() => {
            if (!autoplay || !emblaApi) return;
            const interval = setInterval(() => {
                emblaApi.scrollNext();
            }, autoplayInterval);
            return () => clearInterval(interval);
        }, [autoplay, autoplayInterval, emblaApi]);

        return (
            <div ref={ref} className={cn('relative', className)} {...props}>
                <div ref={emblaRef} className="overflow-hidden rounded-xl">
                    <div className="flex">{children}</div>
                </div>

                {/* Navigation Arrows */}
                {showArrows && (
                    <>
                        <button
                            onClick={scrollPrev}
                            className="absolute top-1/2 left-2 z-10 -translate-y-1/2 rounded-full bg-white/80 p-2 shadow-md transition-colors hover:bg-white dark:bg-gray-800/80 dark:hover:bg-gray-800"
                            aria-label="Previous slide"
                        >
                            <ChevronLeft className="h-5 w-5 text-gray-700 dark:text-gray-200" />
                        </button>
                        <button
                            onClick={scrollNext}
                            className="absolute top-1/2 right-2 z-10 -translate-y-1/2 rounded-full bg-white/80 p-2 shadow-md transition-colors hover:bg-white dark:bg-gray-800/80 dark:hover:bg-gray-800"
                            aria-label="Next slide"
                        >
                            <ChevronRight className="h-5 w-5 text-gray-700 dark:text-gray-200" />
                        </button>
                    </>
                )}

                {/* Dots Indicator */}
                {showDots && scrollSnaps.length > 1 && (
                    <div className="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 gap-2">
                        {scrollSnaps.map((_, index) => (
                            <button
                                key={index}
                                onClick={() => scrollTo(index)}
                                className={cn(
                                    'h-2 w-2 rounded-full transition-colors',
                                    index === selectedIndex ? 'bg-orange-500' : 'bg-white/60 hover:bg-white/80',
                                )}
                                aria-label={`Go to slide ${index + 1}`}
                            />
                        ))}
                    </div>
                )}
            </div>
        );
    },
);

Carousel.displayName = 'Carousel';

interface CarouselSlideProps extends HTMLAttributes<HTMLDivElement> {}

const CarouselSlide = forwardRef<HTMLDivElement, CarouselSlideProps>(({ className, children, ...props }, ref) => {
    return (
        <div ref={ref} className={cn('min-w-0 flex-[0_0_100%]', className)} {...props}>
            {children}
        </div>
    );
});

CarouselSlide.displayName = 'CarouselSlide';

export { Carousel, CarouselSlide };
