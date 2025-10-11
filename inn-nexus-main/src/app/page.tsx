'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { CheckCircle } from 'lucide-react';
import LoginForm from '@/components/LoginForm';

type FormMode = 'login' | 'register' | 'reset';
type UserType = 'user' | 'staff';

export default function LoginPage() {
  const [userType, setUserType] = useState<UserType>('user');
  const [formMode, setFormMode] = useState<FormMode>('login');
  const [showSuccess, setShowSuccess] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const toggleUserType = () => {
    setUserType(prev => prev === 'user' ? 'staff' : 'user');
  };

  const handleFormModeChange = (mode: FormMode) => {
    setFormMode(mode);
  };

  const handleSuccess = (message: string) => {
    setSuccessMessage(message);
    setShowSuccess(true);
    
    // Hide success message after 5 seconds
    setTimeout(() => {
      setShowSuccess(false);
    }, 5000);
  };

  return (
    <div className="min-h-screen bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      {/* Success Message */}
      {showSuccess && (
        <div className="fixed top-4 right-4 z-50">
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            className="bg-green-600 text-white px-6 py-4 rounded-lg shadow-lg flex items-center"
          >
            <CheckCircle className="h-6 w-6 mr-2" />
            <span>{successMessage}</span>
          </motion.div>
        </div>
      )}

      <motion.div 
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="sm:mx-auto sm:w-full sm:max-w-md"
      >
        <div className="flex justify-center">
          <svg className="w-12 h-12 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clipRule="evenodd" />
          </svg>
        </div>
        <h2 className="mt-6 text-center text-3xl font-extrabold text-white">
          {formMode === 'login' ? 'Sign in to your account' : 
           formMode === 'register' ? 'Create an account' : 'Reset your password'}
        </h2>
        <p className="mt-2 text-center text-sm text-gray-400">
          {formMode === 'login' && userType === 'user' && 'Or '}
          {formMode === 'login' && userType === 'user' && (
            <button
              type="button"
              onClick={() => handleFormModeChange('register')}
              className="font-medium text-blue-400 hover:text-blue-300"
            >
              create a new account
            </button>
          )}
          {formMode !== 'login' && (
            <button
              type="button"
              onClick={() => handleFormModeChange('login')}
              className="font-medium text-blue-400 hover:text-blue-300"
            >
              back to sign in
            </button>
          )}
          {formMode === 'login' && userType === 'staff' && (
            <span className="text-gray-500 text-sm">
              Staff accounts require administrator setup
            </span>
          )}
        </p>
      </motion.div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
          <div className="flex justify-center mb-6">
            <div className="inline-flex rounded-md shadow-sm" role="group">
              <button
                type="button"
                onClick={formMode === 'register' ? undefined : toggleUserType}
                disabled={formMode === 'register'}
                className={`px-4 py-2 text-sm font-medium rounded-l-lg ${
                  userType === 'user' 
                    ? 'bg-blue-600 text-white' 
                    : formMode === 'register' 
                      ? 'bg-gray-800 text-gray-500 cursor-not-allowed' 
                      : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                }`}
              >
                <div className="flex items-center">
                  <svg className="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
                  </svg>
                  User
                </div>
              </button>
              <button
                type="button"
                onClick={formMode === 'register' ? undefined : toggleUserType}
                disabled={formMode === 'register'}
                className={`px-4 py-2 text-sm font-medium rounded-r-lg ${
                  userType === 'staff' 
                    ? 'bg-amber-600 text-white' 
                    : formMode === 'register' 
                      ? 'bg-gray-800 text-gray-500 cursor-not-allowed' 
                      : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                }`}
              >
                <div className="flex items-center">
                  <svg className="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v1h8v-1zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-1a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v1h-3zM4.75 12.094A5.973 5.973 0 004 15v1H1v-1a3 3 0 013.75-2.906z" />
                  </svg>
                  Staff
                </div>
              </button>
            </div>
          </div>

          <LoginForm 
            userType={userType}
            formMode={formMode}
            onToggleUserType={toggleUserType}
            onFormModeChange={handleFormModeChange}
            onSuccess={handleSuccess}
          />
        </div>
      </div>
      
      {/* Password Reset Success Modal */}
      <div id="passwordResetSuccess" className="hs-overlay hidden w-full h-full fixed top-0 left-0 z-[60] overflow-x-hidden overflow-y-auto">
        <div className="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
          <div className="relative flex flex-col bg-gray-800 border border-gray-700 shadow-sm rounded-xl">
            <div className="p-4 sm:p-10 text-center overflow-y-auto">
              <span className="mb-4 inline-flex justify-center items-center w-[46px] h-[46px] rounded-full border-4 border-green-100 bg-green-100">
                <CheckCircle className="w-6 h-6 text-green-600" />
              </span>
              <h3 className="text-lg font-bold text-white">Password reset link sent!</h3>
              <p className="mt-2 text-gray-400">We've sent a password reset link to your email address.</p>
              <div className="mt-6 flex justify-center gap-x-4">
                <button 
                  type="button" 
                  className="py-2 px-4 inline-flex justify-center items-center gap-2 rounded-md border border-transparent font-semibold bg-blue-500 text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
                  data-hs-overlay="#passwordResetSuccess"
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
