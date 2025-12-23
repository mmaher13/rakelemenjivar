import Navigation from "@/components/Navigation";
import Footer from "@/components/Footer";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { ArrowRight } from "lucide-react";
import heroImage from "@/assets/hero-main.jpg";

const Index = () => {
  return (
    <div className="min-h-screen bg-background">
      <Navigation />

      {/* Hero Section */}
      <section className="relative h-screen flex items-center justify-center overflow-hidden">
        {/* Background Image */}
        <div className="absolute inset-0">
          <img
            src={heroImage}
            alt="Rakele Menjivar - International Model"
            className="w-full h-full object-cover object-top"
          />
          <div className="absolute inset-0 bg-gradient-to-r from-background via-background/70 to-transparent" />
          <div className="absolute inset-0 bg-gradient-to-t from-background via-transparent to-background/30" />
        </div>

        {/* Content */}
        <div className="relative z-10 max-w-7xl mx-auto px-6 lg:px-12 w-full">
          <div className="max-w-2xl">
            <p className="font-sans text-xs letter-spacing-wider text-primary mb-4 opacity-0 animate-fade-up">
              INTERNATIONAL MODEL
            </p>
            <h1 className="font-serif text-5xl md:text-7xl lg:text-8xl font-light leading-tight mb-6 opacity-0 animate-fade-up delay-100">
              <span className="text-gradient">Rakele</span>
              <br />
              <span className="text-foreground">Menjivar</span>
            </h1>
            <p className="font-sans text-sm md:text-base text-muted-foreground max-w-md mb-8 opacity-0 animate-fade-up delay-200 leading-relaxed">
              Professional fashion model from El Salvador bringing elegance and versatility to every frame. Available for
              editorial, commercial, runway, and brand collaborations worldwide.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 opacity-0 animate-fade-up delay-300">
              <Button variant="hero" size="lg" asChild>
                <Link to="/portfolio">
                  View Portfolio
                  <ArrowRight className="ml-2 h-4 w-4" />
                </Link>
              </Button>
              <Button variant="outline" size="lg" asChild>
                <Link to="/contact">Get in Touch</Link>
              </Button>
            </div>
          </div>
        </div>

        {/* Scroll Indicator */}
        <div className="absolute bottom-10 left-1/2 -translate-x-1/2 opacity-0 animate-fade-up delay-500">
          <div className="flex flex-col items-center gap-2">
            <span className="font-sans text-xs letter-spacing-wide text-muted-foreground">
              SCROLL
            </span>
            <div className="w-px h-10 bg-gradient-to-b from-primary to-transparent" />
          </div>
        </div>
      </section>

      {/* About Section */}
      <section className="py-24 md:py-32">
        <div className="max-w-7xl mx-auto px-6 lg:px-12">
          <div className="grid md:grid-cols-2 gap-12 md:gap-20 items-center">
            <div>
              <p className="font-sans text-xs letter-spacing-wider text-primary mb-4">
                ABOUT
              </p>
              <h2 className="font-serif text-3xl md:text-5xl font-light text-foreground mb-6">
                Where Passion Meets Profession
              </h2>
              <p className="font-sans text-muted-foreground leading-relaxed mb-6">
                As a professional international fashion model from El Salvador, I bring years of
                experience across diverse modeling categories. From high fashion
                editorials to commercial campaigns, luxury brand ambassadorships to runway shows,
                my versatility and professionalism ensure every project exceeds expectations.
              </p>
              <p className="font-sans text-muted-foreground leading-relaxed mb-8">
                I believe in collaborative artistry—working closely with
                photographers, creative directors, and brands worldwide to bring unique
                visions to life. Available for bookings in Latin America, North America, Europe, and internationally.
              </p>
              <Button variant="elegant" asChild>
                <Link to="/contact">Work With Me</Link>
              </Button>
            </div>
            <div className="relative">
              <div className="aspect-[3/4] bg-charcoal-light overflow-hidden">
                <img
                  src={heroImage}
                  alt="Rakele Menjivar"
                  className="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-700"
                />
              </div>
              <div className="absolute -bottom-6 -left-6 w-32 h-32 border border-primary opacity-30" />
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-24 bg-charcoal border-t border-border">
        <div className="max-w-4xl mx-auto px-6 lg:px-12 text-center">
          <p className="font-sans text-xs letter-spacing-wider text-primary mb-4">
            LET'S CREATE TOGETHER
          </p>
          <h2 className="font-serif text-3xl md:text-5xl font-light text-foreground mb-8">
            Ready to bring your vision to life?
          </h2>
          <Button variant="hero" size="lg" asChild>
            <Link to="/contact">
              Start a Conversation
              <ArrowRight className="ml-2 h-4 w-4" />
            </Link>
          </Button>
        </div>
      </section>

      <Footer />
    </div>
  );
};

export default Index;
