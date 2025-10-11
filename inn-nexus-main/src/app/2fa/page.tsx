'use client';

import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { useRouter } from 'next/navigation';

export default function TwoFactorAuthPage() {
  const router = useRouter();
  const [code, setCode] = useState(['', '', '', '', '', '']);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [resendCooldown, setResendCooldown] = useState(30);
  const [authData, setAuthData] = useState<{
    email: string;
    userType: 'user' | 'staff';
    formMode: 'login' | 'register' | 'reset';
  } | null>(null);

  // Load auth data from session storage
  useEffect(() => {
    const storedData = sessionStorage.getItem('authData');
    if (storedData) {
      setAuthData(JSON.parse(storedData));
    } else {
      router.push('/');
    }
  }, [router]);

  // Handle resend cooldown
  useEffect(() => {
    if (resendCooldown > 0) {
      const timer = setTimeout(() => {
        setResendCooldown(resendCooldown - 1);
      }, 1000);
      return () => clearTimeout(timer);
    }
  }, [resendCooldown]);

  const handleResendCode = () => {
    if (resendCooldown > 0) return;
    
    // Reset cooldown
    setResendCooldown(30);
    setError('');
    
    // Simulate API call to resend code
    setIsSubmitting(true);
    setTimeout(() => {
      console.log('Resending code to:', authData?.email);
      setIsSubmitting(false);
      
      // Show success message
      const successModal = document.getElementById('resendSuccess');
      if (successModal) {
        // @ts-ignore - HSOverlay is from Preline
        window.HSOverlay.open(successModal);
      }
    }, 1000);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Basic validation - check if all digits are filled
    if (code.some(digit => !digit)) {
      setError('Please enter the 6-digit code');
      return;
    }
    
    setIsSubmitting(true);
    setError('');
    
    // Simulate API call with 1s delay
    setTimeout(() => {
      // Get user type from auth data
      const storedData = sessionStorage.getItem('authData');
      const userType = storedData ? JSON.parse(storedData).userType : 'user';
      
      // Update auth data with 2FA verified flag
      const authData = storedData ? JSON.parse(storedData) : { userType: 'user' };
      authData.is2FAVerified = true;
      sessionStorage.setItem('authData', JSON.stringify(authData));
      
      // Redirect based on user type
      const redirectPath = userType === 'staff' ? '/dashboard/staff' : '/dashboard/user';
      window.location.href = redirectPath;
      
      setIsSubmitting(false);
    }, 1000);
  };

  const handleChange = (index: number, value: string) => {
    if (value && !/^\d*$/.test(value)) return; // Only allow numbers
    
    const newCode = [...code];
    newCode[index] = value;
    setCode(newCode);
    
    // Auto focus next input
    if (value && index < 5) {
      const nextInput = document.getElementById(`code-${index + 1}`) as HTMLInputElement;
      if (nextInput) nextInput.focus();
    }
  };

  const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Backspace' && !code[index] && index > 0) {
      const prevInput = document.getElementById(`code-${index - 1}`) as HTMLInputElement;
      if (prevInput) prevInput.focus();
    }
  };

  if (!authData) {
    return (
      <div className="min-h-screen bg-gray-900 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <motion.div 
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="sm:mx-auto sm:w-full sm:max-w-md"
      >
        <div className="flex justify-center">
          <svg className="w-12 h-12 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clipRule="evenodd" />
          </svg>
        </div>
        <h2 className="mt-6 text-center text-3xl font-extrabold text-white">
          Two-Factor Authentication
        </h2>
        <p className="mt-2 text-center text-sm text-gray-400">
          We've sent a verification code to {authData?.email || 'your email'}
        </p>
      </motion.div>

      <motion.div 
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.1 }}
        className="mt-8 sm:mx-auto sm:w-full sm:max-w-md"
      >
        <div className="bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
          <form className="space-y-6" onSubmit={handleSubmit}>
            <div>
              <div className="flex justify-center space-x-2">
                {[0, 1, 2, 3, 4, 5].map((i) => (
                  <input
                    key={i}
                    id={`code-${i}`}
                    type="text"
                    inputMode="numeric"
                    maxLength={1}
                    value={code[i]}
                    onChange={(e) => handleChange(i, e.target.value)}
                    onKeyDown={(e) => handleKeyDown(i, e)}
                    className="w-12 h-12 text-center text-2xl font-semibold bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    autoFocus={i === 0}
                  />
                ))}
              </div>
              {error && (
                <p className="mt-2 text-sm text-red-500 text-center">{error}</p>
              )}
            </div>

            <div>
              <button
                type="submit"
                disabled={isSubmitting || code.some(digit => !digit)}
                className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSubmitting ? (
                  <>
                    <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Verifying...
                  </>
                ) : (
                  'Verify Code'
                )}
              </button>
            </div>
          </form>

          <div className="mt-6">
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-600"></div>
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-gray-800 text-gray-400">
                  Having trouble?
                </span>
              </div>
            </div>

            <div className="mt-6 text-center">
              <button
                type="button"
                onClick={handleResendCode}
                disabled={resendCooldown > 0 || isSubmitting}
                className={`text-sm font-medium ${
                  resendCooldown > 0 || isSubmitting
                    ? 'text-gray-500 cursor-not-allowed'
                    : 'text-blue-400 hover:text-blue-300'
                }`}
              >
                {resendCooldown > 0
                  ? `Resend code in ${resendCooldown}s`
                  : 'Resend Code'}
              </button>
            </div>
          </div>
        </div>
      </motion.div>
      
      {/* Resend Success Modal */}
      <div id="resendSuccess" className="hs-overlay hidden w-full h-full fixed top-0 left-0 z-[60] overflow-x-hidden overflow-y-auto">
        <div className="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
          <div className="relative flex flex-col bg-gray-800 border border-gray-700 shadow-sm rounded-xl">
            <div className="p-4 sm:p-10 text-center overflow-y-auto">
              <span className="mb-4 inline-flex justify-center items-center w-[46px] h-[46px] rounded-full border-4 border-green-100 bg-green-100">
                <svg className="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                </svg>
              </span>
              <h3 className="text-lg font-bold text-white">Verification code sent!</h3>
              <p className="mt-2 text-gray-400">We've sent a new verification code to your email address.</p>
              <div className="mt-6 flex justify-center gap-x-4">
                <button 
                  type="button" 
                  className="py-2 px-4 inline-flex justify-center items-center gap-2 rounded-md border border-transparent font-semibold bg-blue-500 text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
                  data-hs-overlay="#resendSuccess"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
