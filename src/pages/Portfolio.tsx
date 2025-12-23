import Navigation from "@/components/Navigation";
import Footer from "@/components/Footer";
import portfolio1 from "@/assets/portfolio-1.jpg";
import portfolio2 from "@/assets/portfolio-2.jpg";
import portfolio3 from "@/assets/portfolio-3.jpg";
import portfolio4 from "@/assets/portfolio-4.jpg";

const Portfolio = () => {
  const portfolioImages = [
    { src: portfolio1, alt: "Editorial Fashion Shoot", category: "Editorial" },
    { src: portfolio2, alt: "High Fashion Portrait", category: "Fashion" },
    { src: portfolio3, alt: "Beauty Campaign", category: "Beauty" },
    { src: portfolio4, alt: "Runway Style", category: "Runway" },
  ];

  return (
    <div className="min-h-screen bg-background">
      <Navigation />

      {/* Hero */}
      <section className="pt-32 pb-16">
        <div className="max-w-7xl mx-auto px-6 lg:px-12">
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

      {/* Portfolio Grid */}
      <section className="py-12 pb-24">
        <div className="max-w-7xl mx-auto px-6 lg:px-12">
          <div className="grid md:grid-cols-2 gap-6 md:gap-8">
            {portfolioImages.map((image, index) => (
              <div
                key={index}
                className="group relative overflow-hidden opacity-0 animate-fade-up hover-lift"
                style={{ animationDelay: `${0.3 + index * 0.1}s` }}
              >
                <div className="aspect-[4/5] overflow-hidden">
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
                  <p className="font-serif text-xl text-foreground">
                    {image.alt}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Experience Section */}
      <section className="py-20 border-t border-border">
        <div className="max-w-7xl mx-auto px-6 lg:px-12">
          <div className="grid md:grid-cols-3 gap-12">
            <div className="text-center md:text-left">
              <p className="font-sans text-xs letter-spacing-wider text-primary mb-4">
                EXPERIENCE
              </p>
              <h2 className="font-serif text-2xl md:text-3xl font-light text-foreground">
                Brands & Publications
              </h2>
            </div>
            <div className="md:col-span-2">
              <div className="grid grid-cols-2 md:grid-cols-3 gap-8">
                {[
                  "Vogue Mexico",
                  "Harper's Bazaar",
                  "ELLE",
                  "L'Officiel",
                  "Glamour",
                  "Marie Claire",
                ].map((brand) => (
                  <p
                    key={brand}
                    className="font-serif text-lg text-muted-foreground hover:text-foreground transition-colors duration-300"
                  >
                    {brand}
                  </p>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
};

export default Portfolio;
