import { useState } from "react";
import Navigation from "@/components/Navigation";
import Footer from "@/components/Footer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { useToast } from "@/hooks/use-toast";
import { Mail, MapPin, Instagram } from "lucide-react";

const Contact = () => {
  const { toast } = useToast();
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    company: "",
    message: "",
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    // Simulate form submission
    await new Promise((resolve) => setTimeout(resolve, 1000));

    toast({
      title: "Message Sent",
      description: "Thank you for reaching out. I'll get back to you soon.",
    });

    setFormData({ name: "", email: "", company: "", message: "" });
    setIsSubmitting(false);
  };

  return (
    <div className="min-h-screen bg-background">
      <Navigation />

      <section className="pt-32 pb-24">
        <div className="max-w-7xl mx-auto px-6 lg:px-12">
          <div className="grid lg:grid-cols-2 gap-16 lg:gap-24">
            {/* Left Column - Info */}
            <div>
              <p className="font-sans text-xs letter-spacing-wider text-primary mb-4 opacity-0 animate-fade-up">
                CONTACT
              </p>
              <h1 className="font-serif text-4xl md:text-6xl font-light text-foreground mb-6 opacity-0 animate-fade-up delay-100">
                Let's Work Together
              </h1>
              <p className="font-sans text-muted-foreground mb-12 opacity-0 animate-fade-up delay-200 leading-relaxed">
                Available for editorial, commercial, runway, and brand
                collaborations. Whether you have a specific project in mind or
                want to explore possibilities, I'd love to hear from you.
              </p>

              {/* Contact Info */}
              <div className="space-y-8 opacity-0 animate-fade-up delay-300">
                <div className="flex items-start gap-4">
                  <div className="w-10 h-10 border border-border flex items-center justify-center text-primary">
                    <Mail size={18} />
                  </div>
                  <div>
                    <p className="font-sans text-xs letter-spacing-wide text-muted-foreground mb-1">
                      EMAIL
                    </p>
                    <a
                      href="mailto:contact@rakelemenjivar.com"
                      className="font-sans text-foreground hover:text-primary transition-colors duration-300"
                    >
                      contact@rakelemenjivar.com
                    </a>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="w-10 h-10 border border-border flex items-center justify-center text-primary">
                    <MapPin size={18} />
                  </div>
                  <div>
                    <p className="font-sans text-xs letter-spacing-wide text-muted-foreground mb-1">
                      BASED IN
                    </p>
                    <p className="font-sans text-foreground">
                      Los Angeles, California
                    </p>
                  </div>
                </div>

                <div className="flex items-start gap-4">
                  <div className="w-10 h-10 border border-border flex items-center justify-center text-primary">
                    <Instagram size={18} />
                  </div>
                  <div>
                    <p className="font-sans text-xs letter-spacing-wide text-muted-foreground mb-1">
                      INSTAGRAM
                    </p>
                    <a
                      href="https://instagram.com/rakelemenjivar"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="font-sans text-foreground hover:text-primary transition-colors duration-300"
                    >
                      @rakelemenjivar
                    </a>
                  </div>
                </div>
              </div>
            </div>

            {/* Right Column - Form */}
            <div className="opacity-0 animate-fade-up delay-400">
              <form onSubmit={handleSubmit} className="space-y-6">
                <div>
                  <label
                    htmlFor="name"
                    className="block font-sans text-xs letter-spacing-wide text-muted-foreground mb-2"
                  >
                    YOUR NAME *
                  </label>
                  <Input
                    id="name"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    required
                    placeholder="John Doe"
                  />
                </div>

                <div>
                  <label
                    htmlFor="email"
                    className="block font-sans text-xs letter-spacing-wide text-muted-foreground mb-2"
                  >
                    EMAIL ADDRESS *
                  </label>
                  <Input
                    id="email"
                    name="email"
                    type="email"
                    value={formData.email}
                    onChange={handleChange}
                    required
                    placeholder="john@company.com"
                  />
                </div>

                <div>
                  <label
                    htmlFor="company"
                    className="block font-sans text-xs letter-spacing-wide text-muted-foreground mb-2"
                  >
                    COMPANY / AGENCY
                  </label>
                  <Input
                    id="company"
                    name="company"
                    value={formData.company}
                    onChange={handleChange}
                    placeholder="Your company name"
                  />
                </div>

                <div>
                  <label
                    htmlFor="message"
                    className="block font-sans text-xs letter-spacing-wide text-muted-foreground mb-2"
                  >
                    PROJECT DETAILS *
                  </label>
                  <Textarea
                    id="message"
                    name="message"
                    value={formData.message}
                    onChange={handleChange}
                    required
                    placeholder="Tell me about your project, timeline, and vision..."
                  />
                </div>

                <Button
                  type="submit"
                  variant="hero"
                  size="lg"
                  className="w-full"
                  disabled={isSubmitting}
                >
                  {isSubmitting ? "Sending..." : "Send Message"}
                </Button>
              </form>
            </div>
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
};

export default Contact;
