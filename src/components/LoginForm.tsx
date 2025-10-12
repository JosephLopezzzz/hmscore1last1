'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { Mail, Lock, Eye, EyeOff, AlertCircle, CheckCircle } from 'lucide-react';

type FormMode = 'login' | 'register' | 'reset';
type UserType = 'user' | 'staff';

interface LoginFormProps {
  userType: UserType;
  formMode: FormMode;
  onToggleUserType: () => void;
  onFormModeChange: (mode: FormMode) => void;
  onSuccess: (message: string) => void;
}

export default function LoginForm({ 
  userType, 
  formMode, 
  onToggleUserType,
  onFormModeChange,
  onSuccess 
}: LoginFormProps) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const validateForm = () => {
    const errors: Record<string, string> = {};
    const trimmedEmail = email.trim();
    const trimmedPassword = password.trim();
    const trimmedConfirmPassword = confirmPassword.trim();

    // Email validation
    if (!trimmedEmail) {
      errors.email = 'Email is required';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmedEmail)) {
      errors.email = 'Please enter a valid email address';
    }

    // Password validation for login/register
    if (formMode !== 'reset') {
      if (!trimmedPassword) {
        errors.password = 'Password is required';
      } else if (trimmedPassword.length < 8) {
        errors.password = 'Password must be at least 8 characters long';
      } else if (formMode === 'register') {
        if (!/(?=.*[a-z])/.test(trimmedPassword)) {
          errors.password = 'Password must contain at least one lowercase letter';
        } else if (!/(?=.*[A-Z])/.test(trimmedPassword)) {
          errors.password = 'Password must contain at least one uppercase letter';
        } else if (!/(?=.*\d)/.test(trimmedPassword)) {
          errors.password = 'Password must contain at least one number';
        } else if (!/(?=.*[!@#$%^&*])/.test(trimmedPassword)) {
          errors.password = 'Password must contain at least one special character';
        }
      }

      // Confirm password validation for registration
      if (formMode === 'register' && trimmedPassword !== trimmedConfirmPassword) {
        errors.confirmPassword = 'Passwords do not match';
      }
    }

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    setIsSubmitting(true);
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      console.log('Form submitted:', { 
        email: email.trim(),
        userType,
        formMode 
      });
      
      // Redirect to 2FA page on successful login/registration
      if (formMode === 'login' || formMode === 'register') {
        // Store form data in session storage for 2FA page
        sessionStorage.setItem('authData', JSON.stringify({
          email: email.trim(),
          userType,
          formMode
        }));
        window.location.href = '/2fa';
      } else {
        // Reset password success
        onFormModeChange('login');
        onSuccess('Password reset instructions sent to your email');
      }
    } catch (error) {
      console.error('Form submission error:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form className="space-y-6" onSubmit={handleSubmit} noValidate>
      <div>
        <label htmlFor="email" className="sr-only">Email address</label>
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Mail className="h-5 w-5 text-gray-400" />
          </div>
          <input
            id="email"
            name="email"
            type="email"
            autoComplete="email"
            value={email}
            onChange={(e) => {
              setEmail(e.target.value);
              if (formErrors.email) {
                setFormErrors(prev => ({ ...prev, email: '' }));
              }
            }}
            className={`block w-full pl-10 pr-10 py-2 border ${
              formErrors.email ? 'border-red-500' : 'border-gray-600'
            } rounded-md shadow-sm bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm`}
            placeholder="Email address"
          />
          {formErrors.email && (
            <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <AlertCircle className="h-5 w-5 text-red-500" />
            </div>
          )}
        </div>
        {formErrors.email && (
          <p className="mt-2 text-sm text-red-500">{formErrors.email}</p>
        )}
      </div>

      {formMode !== 'reset' && (
        <div>
          <label htmlFor="password" className="sr-only">Password</label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Lock className="h-5 w-5 text-gray-400" />
            </div>
            <input
              id="password"
              name="password"
              type={showPassword ? 'text' : 'password'}
              autoComplete={formMode === 'login' ? 'current-password' : 'new-password'}
              value={password}
              onChange={(e) => {
                setPassword(e.target.value);
                if (formErrors.password) {
                  setFormErrors(prev => ({ ...prev, password: '' }));
                }
              }}
              className={`block w-full pl-10 pr-10 py-2 border ${
                formErrors.password ? 'border-red-500' : 'border-gray-600'
              } rounded-md shadow-sm bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm`}
              placeholder="Password"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              {showPassword ? (
                <EyeOff className="h-5 w-5 text-gray-400" />
              ) : (
                <Eye className="h-5 w-5 text-gray-400" />
              )}
            </button>
          </div>
          {formErrors.password && (
            <p className="mt-2 text-sm text-red-500">{formErrors.password}</p>
          )}
        </div>
      )}

      {formMode === 'register' && (
        <div>
          <label htmlFor="confirmPassword" className="sr-only">Confirm Password</label>
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Lock className="h-5 w-5 text-gray-400" />
            </div>
            <input
              id="confirmPassword"
              name="confirmPassword"
              type={showConfirmPassword ? 'text' : 'password'}
              autoComplete="new-password"
              value={confirmPassword}
              onChange={(e) => {
                setConfirmPassword(e.target.value);
                if (formErrors.confirmPassword) {
                  setFormErrors(prev => ({ ...prev, confirmPassword: '' }));
                }
              }}
              className={`block w-full pl-10 pr-10 py-2 border ${
                formErrors.confirmPassword ? 'border-red-500' : 'border-gray-600'
              } rounded-md shadow-sm bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm`}
              placeholder="Confirm Password"
            />
            <button
              type="button"
              onClick={() => setShowConfirmPassword(!showConfirmPassword)}
              className="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              {showConfirmPassword ? (
                <EyeOff className="h-5 w-5 text-gray-400" />
              ) : (
                <Eye className="h-5 w-5 text-gray-400" />
              )}
            </button>
          </div>
          {formErrors.confirmPassword && (
            <p className="mt-2 text-sm text-red-500">{formErrors.confirmPassword}</p>
          )}
        </div>
      )}

      <div className="flex items-center justify-between">
        {formMode === 'login' && (
          <div className="flex items-center">
            <input
              id="remember-me"
              name="remember-me"
              type="checkbox"
              checked={rememberMe}
              onChange={(e) => setRememberMe(e.target.checked)}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-600 rounded bg-gray-700"
            />
            <label htmlFor="remember-me" className="ml-2 block text-sm text-gray-300">
              Remember me
            </label>
          </div>
        )}

        {formMode !== 'register' && (
          <div className="text-sm">
            <button
              type="button"
              onClick={() => onFormModeChange(formMode === 'login' ? 'reset' : 'login')}
              className="font-medium text-blue-400 hover:text-blue-300"
            >
              {formMode === 'login' ? 'Forgot your password?' : 'Back to login'}
            </button>
          </div>
        )}
      </div>

      <div>
        <button
          type="submit"
          disabled={isSubmitting}
          className={`w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white ${
            isSubmitting
              ? 'bg-blue-400 cursor-not-allowed'
              : 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
          }`}
        >
          {isSubmitting ? (
            'Processing...'
          ) : formMode === 'login' ? (
            'Sign in'
          ) : formMode === 'register' ? (
            'Register'
          ) : (
            'Reset Password'
          )}
        </button>
      </div>
    </form>
  );
}
