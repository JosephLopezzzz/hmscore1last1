'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

export default function DashboardPage() {
  const router = useRouter();

  useEffect(() => {
    // Check if we have auth data to determine user type
    const storedData = sessionStorage.getItem('authData');
    const userType = storedData ? JSON.parse(storedData).userType : 'user';

    // Redirect based on user type
    const redirectPath = userType === 'staff' ? '/dashboard/staff' : '/dashboard/user';
    router.push(redirectPath);
  }, [router]);

  // Show loading state while redirecting
  return (
    <div className="min-h-screen bg-gray-900 flex items-center justify-center">
      <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
    </div>
  );
}
