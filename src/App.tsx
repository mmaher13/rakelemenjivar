import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import Index from "./pages/Index";
import Portfolio from "./pages/Portfolio";
import Contact from "./pages/Contact";
import NotFound from "./pages/NotFound";
import { usePageTracking } from "./hooks/usePageTracking";

const queryClient = new QueryClient();

export const RouteTable = () => (
  <Routes>
    <Route path="/" element={<Index />} />
    <Route path="/portfolio" element={<Portfolio />} />
    <Route path="/contact" element={<Contact />} />
    <Route path="*" element={<NotFound />} />
  </Routes>
);

const AppRoutes = () => {
  usePageTracking();

  return <RouteTable />;
};

export const AppShell = ({ children }: { children: React.ReactNode }) => (
  <QueryClientProvider client={queryClient}>
    <TooltipProvider>
      <Toaster />
      <Sonner />
      {children}
    </TooltipProvider>
  </QueryClientProvider>
);

const App = () => (
  <AppShell>
    <BrowserRouter>
      <AppRoutes />
    </BrowserRouter>
  </AppShell>
);

export default App;
