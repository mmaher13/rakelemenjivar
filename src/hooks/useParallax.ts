import { useState, useEffect, useCallback, useRef } from "react";

interface ParallaxOptions {
  speed?: number;
  direction?: "up" | "down";
}

export const useParallax = (options: ParallaxOptions = {}) => {
  const { speed = 0.5, direction = "up" } = options;
  const [offset, setOffset] = useState(0);
  const ticking = useRef(false);

  const updateOffset = useCallback(() => {
    const scrollY = window.scrollY;
    const multiplier = direction === "up" ? -1 : 1;
    setOffset(scrollY * speed * multiplier);
    ticking.current = false;
  }, [speed, direction]);

  const handleScroll = useCallback(() => {
    if (!ticking.current) {
      requestAnimationFrame(updateOffset);
      ticking.current = true;
    }
  }, [updateOffset]);

  useEffect(() => {
    // Check for reduced motion preference
    const prefersReducedMotion = window.matchMedia(
      "(prefers-reduced-motion: reduce)"
    ).matches;

    if (prefersReducedMotion) {
      return;
    }

    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => window.removeEventListener("scroll", handleScroll);
  }, [handleScroll]);

  return offset;
};

// Hook for element-based parallax (relative to element position)
export const useElementParallax = (options: ParallaxOptions = {}) => {
  const { speed = 0.3, direction = "up" } = options;
  const [offset, setOffset] = useState(0);
  const elementRef = useRef<HTMLDivElement>(null);
  const ticking = useRef(false);

  const updateOffset = useCallback(() => {
    if (!elementRef.current) return;

    const rect = elementRef.current.getBoundingClientRect();
    const windowHeight = window.innerHeight;
    const elementCenter = rect.top + rect.height / 2;
    const viewportCenter = windowHeight / 2;
    const distanceFromCenter = elementCenter - viewportCenter;
    const multiplier = direction === "up" ? -1 : 1;

    setOffset(distanceFromCenter * speed * multiplier);
    ticking.current = false;
  }, [speed, direction]);

  const handleScroll = useCallback(() => {
    if (!ticking.current) {
      requestAnimationFrame(updateOffset);
      ticking.current = true;
    }
  }, [updateOffset]);

  useEffect(() => {
    const prefersReducedMotion = window.matchMedia(
      "(prefers-reduced-motion: reduce)"
    ).matches;

    if (prefersReducedMotion) {
      return;
    }

    window.addEventListener("scroll", handleScroll, { passive: true });
    updateOffset(); // Initial calculation
    return () => window.removeEventListener("scroll", handleScroll);
  }, [handleScroll, updateOffset]);

  return { ref: elementRef, offset };
};
