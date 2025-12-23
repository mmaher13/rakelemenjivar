import { Instagram, Mail } from "lucide-react";
import { SiTiktok } from "react-icons/si";

const Footer = () => {
  return (
    <footer className="bg-charcoal border-t border-border py-12">
      <div className="max-w-7xl mx-auto px-6 lg:px-12">
        <div className="flex flex-col md:flex-row items-center justify-between gap-6">
          <p className="font-logo text-xl tracking-wider text-foreground">
            Rakele Menjivar
          </p>
          
          <div className="flex items-center gap-6">
            <a
              href="https://www.instagram.com/rakelemenjivar"
              target="_blank"
              rel="noopener noreferrer"
              className="text-muted-foreground hover:text-primary transition-colors duration-300"
              aria-label="Instagram"
            >
              <Instagram size={20} />
            </a>
            <a
              href="https://www.tiktok.com/@rakelemenjivar"
              target="_blank"
              rel="noopener noreferrer"
              className="text-muted-foreground hover:text-primary transition-colors duration-300"
              aria-label="TikTok"
            >
              <SiTiktok size={20} />
            </a>
            <a
              href="mailto:rakele@rakelemenjivar.com"
              className="text-muted-foreground hover:text-primary transition-colors duration-300"
              aria-label="Email"
            >
              <Mail size={20} />
            </a>
          </div>
          
          <p className="font-sans text-xs text-muted-foreground letter-spacing-wide">
            © {new Date().getFullYear()} ALL RIGHTS RESERVED
          </p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
