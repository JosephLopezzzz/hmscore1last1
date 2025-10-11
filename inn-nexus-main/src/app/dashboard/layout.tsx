'use client';

import { useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const [isMounted, setIsMounted] = useState(false);
  const router = useRouter();

  useEffect(() => {
    // Check if user is authenticated and has completed 2FA
    const authData = sessionStorage.getItem('authData');
    if (!authData) {
      router.push('/');
      return;
    }
    
    const parsedAuth = JSON.parse(authData);
    const { userType, is2FAVerified } = parsedAuth;
    const currentPath = window.location.pathname;
    
    // Redirect to 2FA if not verified
    if (!is2FAVerified && !currentPath.includes('2fa')) {
      router.push('/2fa');
      return;
    }
    
    // Redirect to appropriate dashboard if at root
    if (currentPath === '/dashboard') {
      const redirectPath = userType === 'staff' ? '/dashboard/staff' : '/dashboard/user';
      router.push(redirectPath);
      return;
    }
    
    // Check if user is trying to access wrong dashboard
    if ((userType === 'staff' && currentPath.startsWith('/dashboard/user')) ||
        (userType === 'user' && currentPath.startsWith('/dashboard/staff'))) {
      const redirectPath = userType === 'staff' ? '/dashboard/staff' : '/dashboard/user';
      router.push(redirectPath);
      return;
    }
    
    setIsMounted(true);
  }, [router]);

  if (!isMounted) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-100">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  return <>{children}</>;
}
