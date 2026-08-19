import Navigation from "@/components/Navigation";
import Footer from "@/components/Footer";
import portfolio1 from "@/assets/portfolio-1.jpg";
import portfolio2 from "@/assets/portfolio-2.jpg";
import portfolio3 from "@/assets/portfolio-3.jpg";
import portfolio4 from "@/assets/portfolio-4.jpg";
import portfolio5 from "@/assets/portfolio-5.jpg";
import portfolio6 from "@/assets/portfolio-6.jpg";
import portfolio7 from "@/assets/portfolio-7.jpg";
import portfolio8 from "@/assets/portfolio-8.jpg";
import portfolio9 from "@/assets/portfolio-9.jpg";
import portfolio10 from "@/assets/portfolio-10.jpg";
import portfolio11 from "@/assets/portfolio-11.jpg";
import portfolio12 from "@/assets/portfolio-12.jpg";
import portfolio13 from "@/assets/portfolio-13.jpg";
import portfolio14 from "@/assets/portfolio-14.jpg";
import { useParallax } from "@/hooks/useParallax";

const PortfolioImage = ({
  image,
  index,
}: {
  image: { src: string; alt: string; category: string };
  index: number;
}) => {
  const parallax = useParallax({
    speed: 0.03,
    direction: index % 2 === 0 ? "up" : "down",
  });

  return (
    <div
      className="group relative overflow-hidden opacity-0 animate-fade-up hover-lift will-change-transform"
      style={{
        animationDelay: `${0.3 + index * 0.05}s`,
        transform: `translateY(${parallax}px)`,
      }}
    >
      <div className="aspect-[3/4] overflow-hidden">
        <img
          src={image.src}
          alt={image.alt}
          className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
        />
      </div>
      <div className="absolute inset-0 bg-gradient-to-t from-background/90 via-background/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
        <p className="font-sans text-xs letter-spacing-wide text-primary mb-2">
          {image.category.toUpperCase()}
        </p>
        <p className="font-serif text-xl text-foreground">{image.alt}</p>
      </div>
    </div>
  );
};

const Portfolio = () => {
  const heroParallax = useParallax({ speed: 0.2, direction: "down" });

  const portfolioImages = [
    { src: portfolio1, alt: "Editorial Portrait", category: "Editorial" },
    { src: portfolio2, alt: "Cannes Red Carpet", category: "Red Carpet" },
    { src: portfolio14, alt: "Imperial Garden", category: "Couture" },
    { src: portfolio3, alt: "Polka Dot Romance", category: "Fashion" },
    { src: portfolio4, alt: "Window Light", category: "Editorial" },
    { src: portfolio5, alt: "Noir Hallway", category: "Fashion" },
    { src: portfolio6, alt: "Coastal Couture", category: "Couture" },
    { src: portfolio7, alt: "Sunlit Beauty", category: "Beauty" },
    { src: portfolio8, alt: "Black Glimmer", category: "Couture" },
    { src: portfolio9, alt: "Crimson Brocade", category: "Editorial" },
    { src: portfolio10, alt: "Modern Elegance", category: "Editorial" },
    { src: portfolio11, alt: "Riviera Drive", category: "Lifestyle" },
    { src: portfolio12, alt: "City Lights", category: "Fashion" },
    { src: portfolio13, alt: "Beaded Reverie", category: "Editorial" },
  ];

  const measurements = [
    { label: "Height", value: "1.72 m" },
    { label: "Bust", value: "90 cm (32A)" },
    { label: "Waist", value: "60 cm" },
    { label: "Hips", value: "92 cm" },
    { label: "Dress Size", value: "0 (US)" },
    { label: "Shoe Size", value: "7 US / 38 EU" },
    { label: "Eyes", value: "Brown" },
    { label: "Hair", value: "Black" },
  ];

  return (
    <div className="min-h-screen bg-background">
      <Navigation />

      {/* Hero */}
      <section className="pt-32 pb-16 relative overflow-hidden">
        <div
          className="absolute inset-0 bg-gradient-to-b from-charcoal/50 to-transparent will-change-transform"
          style={{
            transform: `translateY(${heroParallax}px)`,
          }}
        />
        <div className="max-w-7xl mx-auto px-6 lg:px-12 relative z-10">
          <p className="font-sans text-xs letter-spacing-wider text-primary mb-4 opacity-0 animate-fade-up">
            PORTFOLIO
          </p>
          <h1 className="font-serif text-4xl md:text-6xl font-light text-foreground mb-6 opacity-0 animate-fade-up delay-100">
            Selected Works
          </h1>
          <p className="font-sans text-muted-foreground max-w-xl opacity-0 animate-fade-up delay-200">
            A curated collection of editorial, commercial, and personal projects
            showcasing versatility and artistic vision.
          </p>
        </div>
      </section>

      {/* Measurements Bar */}
      <section className="py-8 border-t border-b border-border mb-12">
        <div className="max-w-7xl mx-auto px-6 lg:px-12">
          <div className="flex flex-wrap justify-center gap-8 md:gap-12">
            {measurements.map((item, index) => (
              <div
                key={item.label}
                className="text-center opacity-0 animate-fade-up"
                style={{ animationDelay: `${0.3 + index * 0.05}s` }}
              >
                <p className="font-serif text-lg md:text-xl text-foreground">
                  {item.value}
                </p>
                <p className="font-sans text-xs letter-spacing-wide text-muted-foreground uppercase">
                  {item.label}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Portfolio Grid */}
      <section className="py-12 pb-24">
        <div className="max-w-7xl mx-auto px-6 lg:px-12">
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            {portfolioImages.map((image, index) => (
              <PortfolioImage key={index} image={image} index={index} />
            ))}
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
};

export default Portfolio;
