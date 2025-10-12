import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import PrelineScriptWrapper from './components/PrelineScriptWrapper';


const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "HMS CORE 1",
  description: "Hotel Management System Core Application",
  keywords: ["hotel", "management", "system", "hospitality", "booking"],
  authors: [{ name: "HMS Team" }],
  viewport: "width=device-width, initial-scale=1",
  themeColor: "#1e40af",
  icons: {
    icon: '/star.svg',
    shortcut: '/star.svg',
    apple: '/star.svg'
  }
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" className="dark">
      <body
        className={`${geistSans.variable} ${geistMono.variable} antialiased bg-gray-50 dark:bg-gray-900 dark:text-white`}
      >
        <div className="min-h-screen dark:bg-gray-900">
          {children}
        </div>
        <PrelineScriptWrapper />
      </body>
    </html>
  );
}
