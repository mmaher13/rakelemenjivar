import { useMemo } from "react";

export const useProtectedEmail = () => {
  const emailParts = useMemo(() => {
    // Split email to prevent bot scraping
    const user = ["b", "o", "o", "k", "i", "n", "g"].join("");
    const domain = ["r", "a", "k", "e", "l", "e", "m", "e", "n", "j", "i", "v", "a", "r"].join("");
    const tld = ["c", "o", "m"].join("");
    
    return {
      full: `${user}@${domain}.${tld}`,
      mailto: `mailto:${user}@${domain}.${tld}`,
    };
  }, []);

  return emailParts;
};
