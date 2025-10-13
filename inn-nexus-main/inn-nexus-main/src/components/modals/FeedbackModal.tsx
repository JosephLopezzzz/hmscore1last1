'use client';

import { useState } from 'react';
import Modal from '@/components/ui/Modal';
import { X, Star, MessageSquare, Smile, Frown, Meh, Search, Filter, CheckCircle2, ChevronDown, Plus, Bed, Utensils, Sparkles } from 'lucide-react';

type FeedbackRating = 1 | 2 | 3 | 4 | 5;
type FeedbackStatus = 'new' | 'in-progress' | 'resolved' | 'archived';
type FeedbackType = 'complaint' | 'suggestion' | 'compliment' | 'general';

import { FeedbackModalProps } from './types';

interface Feedback {
  id: string;
  guestName: string;
  email: string;
  stayDate: string;
  rating: FeedbackRating;
  type: FeedbackType;
  message: string;
  status: FeedbackStatus;
  date: string;
  assignedTo?: string;
  response?: string;
  responseDate?: string;
}

export default function FeedbackModal({ isOpen, onClose }: FeedbackModalProps) {
  const [activeTab, setActiveTab] = useState<'list' | 'new'>('list');
  const [selectedFeedback, setSelectedFeedback] = useState<Feedback | null>(null);
  
  // Sample data
  const feedbackList: Feedback[] = [
    {
      id: 'FB-1001',
      guestName: 'John Smith',
      email: 'john.smith@example.com',
      stayDate: '2023-06-10',
      rating: 4,
      type: 'suggestion',
      message: 'The room was great, but the air conditioning was a bit noisy at night. Maybe consider maintenance?',
      status: 'resolved',
      date: '2023-06-12T14:30:00Z',
      assignedTo: 'Sarah K.',
      response: 'Thank you for your feedback. We\'ve scheduled maintenance for the AC unit in room 420.',
      responseDate: '2023-06-12T16:45:00Z'
    },
    {
      id: 'FB-1002',
      guestName: 'Emma Wilson',
      email: 'emma.w@example.com',
      stayDate: '2023-06-15',
      rating: 5,
      type: 'compliment',
      message: 'The staff was incredibly helpful throughout our stay. Special thanks to Michael at the front desk!',
      status: 'new',
      date: '2023-06-16T09:15:00Z'
    },
    {
      id: 'FB-1003',
      guestName: 'Robert Chen',
      email: 'robert.c@example.com',
      stayDate: '2023-06-14',
      rating: 2,
      type: 'complaint',
      message: 'The bathroom was not properly cleaned when we checked in. Also, the TV remote didn\'t work.',
      status: 'in-progress',
      date: '2023-06-15T18:20:00Z',
      assignedTo: 'John D.'
    },
  ];

  const RatingDisplay = ({ rating }: { rating: FeedbackRating }) => (
    <div className="flex items-center">
      {[1, 2, 3, 4, 5].map((star) => (
        <Star 
          key={star}
          className={`w-4 h-4 ${star <= rating ? 'text-yellow-400 fill-current' : 'text-gray-400'}`}
        />
      ))}
    </div>
  );

  const StatusBadge = ({ status }: { status: FeedbackStatus }) => {
    const styles = {
      'new': 'bg-blue-100 text-blue-800',
      'in-progress': 'bg-yellow-100 text-yellow-800',
      'resolved': 'bg-green-100 text-green-800',
      'archived': 'bg-gray-200 text-gray-800'
    };
    
    const labels = {
      'new': 'New',
      'in-progress': 'In Progress',
      'resolved': 'Resolved',
      'archived': 'Archived'
    };
    
    return (
      <span className={`text-xs px-2 py-1 rounded-full ${styles[status]}`}>
        {labels[status]}
      </span>
    );
  };

  const TypeBadge = ({ type }: { type: FeedbackType }) => {
    const styles = {
      'complaint': 'bg-red-100 text-red-800',
      'suggestion': 'bg-blue-100 text-blue-800',
      'compliment': 'bg-green-100 text-green-800',
      'general': 'bg-gray-100 text-gray-800'
    };
    
    const icons = {
      'complaint': <Frown className="w-3 h-3 mr-1" />,
      'suggestion': <MessageSquare className="w-3 h-3 mr-1" />,
      'compliment': <Smile className="w-3 h-3 mr-1" />,
      'general': <MessageSquare className="w-3 h-3 mr-1" />
    };
    
    return (
      <span className={`inline-flex items-center text-xs px-2 py-1 rounded-full ${styles[type]}`}>
        {icons[type]}
        {type.charAt(0).toUpperCase() + type.slice(1)}
      </span>
    );
  };

  const FeedbackItem = ({ feedback, onClick }: { feedback: Feedback; onClick: () => void }) => (
    <div 
      className="p-4 border border-gray-700 rounded-lg hover:bg-gray-800 cursor-pointer transition-colors mb-2"
      onClick={onClick}
    >
      <div className="flex justify-between items-start">
        <div>
          <div className="flex items-center space-x-2">
            <h4 className="font-medium">{feedback.guestName}</h4>
            <TypeBadge type={feedback.type} />
            <StatusBadge status={feedback.status} />
          </div>
          <p className="text-sm text-gray-400 mt-1 line-clamp-2">{feedback.message}</p>
          <div className="flex items-center mt-2 space-x-4">
            <RatingDisplay rating={feedback.rating} />
            <span className="text-xs text-gray-500">
              Stayed on {new Date(feedback.stayDate).toLocaleDateString()}
            </span>
          </div>
        </div>
        <div className="text-right">
          <div className="text-xs text-gray-400">
            {new Date(feedback.date).toLocaleDateString()}
          </div>
          {feedback.assignedTo && (
            <div className="text-xs text-gray-500 mt-1">
              Assigned to {feedback.assignedTo}
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const FeedbackDetail = ({ feedback, onClose }: { feedback: Feedback; onClose: () => void }) => (
    <div className="bg-gray-800 p-6 rounded-lg">
      <div className="flex justify-between items-start mb-6">
        <div>
          <h3 className="text-lg font-medium">Feedback #{feedback.id}</h3>
          <div className="flex items-center space-x-2 mt-1">
            <TypeBadge type={feedback.type} />
            <StatusBadge status={feedback.status} />
          </div>
        </div>
        <button 
          onClick={onClose}
          className="text-gray-400 hover:text-white"
        >
          <X className="w-5 h-5" />
        </button>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div className="md:col-span-2">
          <div className="bg-gray-700 rounded-lg p-4">
            <div className="flex justify-between items-start">
              <div>
                <h4 className="font-medium">{feedback.guestName}</h4>
                <p className="text-sm text-gray-400">{feedback.email}</p>
                <div className="mt-2">
                  <RatingDisplay rating={feedback.rating} />
                </div>
              </div>
              <div className="text-right">
                <div className="text-sm text-gray-400">
                  Stayed on {new Date(feedback.stayDate).toLocaleDateString()}
                </div>
                <div className="text-xs text-gray-500 mt-1">
                  Submitted on {new Date(feedback.date).toLocaleString()}
                </div>
              </div>
            </div>
            
            <div className="mt-4 pt-4 border-t border-gray-600">
              <h5 className="text-sm font-medium text-gray-300 mb-2">Message</h5>
              <p className="text-gray-200 whitespace-pre-line">{feedback.message}</p>
            </div>
          </div>
          
          {feedback.response && (
            <div className="mt-4 bg-blue-900/30 rounded-lg p-4 border border-blue-800/50">
              <div className="flex justify-between items-start">
                <h5 className="text-sm font-medium text-blue-300 mb-2">Your Response</h5>
                <div className="text-xs text-blue-400">
                  {feedback.responseDate && new Date(feedback.responseDate).toLocaleString()}
                </div>
              </div>
              <p className="text-blue-100 whitespace-pre-line">{feedback.response}</p>
            </div>
          )}
          
          <div className="mt-6">
            <h5 className="text-sm font-medium text-gray-300 mb-2">Add Response</h5>
            <textarea 
              className="w-full h-32 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Type your response here..."
              defaultValue={feedback.response || ''}
            ></textarea>
            <div className="mt-2 flex justify-end space-x-3">
              <button className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg">
                Save Draft
              </button>
              <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                Send Response
              </button>
            </div>
          </div>
        </div>
        
        <div className="space-y-4">
          <div className="bg-gray-700 rounded-lg p-4">
            <h5 className="text-sm font-medium text-gray-300 mb-3">Feedback Details</h5>
            <div className="space-y-3">
              <div>
                <div className="text-xs text-gray-400 mb-1">Status</div>
                <select 
                  className="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  defaultValue={feedback.status}
                >
                  <option value="new">New</option>
                  <option value="in-progress">In Progress</option>
                  <option value="resolved">Resolved</option>
                  <option value="archived">Archived</option>
                </select>
              </div>
              
              <div>
                <div className="text-xs text-gray-400 mb-1">Assigned To</div>
                <select 
                  className="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  defaultValue={feedback.assignedTo || ''}
                >
                  <option value="">Unassigned</option>
                  <option value="John D.">John D. (Manager)</option>
                  <option value="Sarah K.">Sarah K. (Front Desk)</option>
                  <option value="Mike S.">Mike S. (Maintenance)</option>
                  <option value="Emma W.">Emma W. (Guest Services)</option>
                </select>
              </div>
              
              <div>
                <div className="text-xs text-gray-400 mb-1">Priority</div>
                <div className="flex space-x-2">
                  <button className="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">High</button>
                  <button className="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Medium</button>
                  <button className="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Low</button>
                </div>
              </div>
              
              <div className="pt-2 mt-2 border-t border-gray-600">
                <div className="text-xs text-gray-400 mb-1">Tags</div>
                <div className="flex flex-wrap gap-2">
                  {['Housekeeping', 'Room Service', 'Amenities', 'Staff', 'Facilities'].map(tag => (
                    <span key={tag} className="px-2 py-0.5 text-xs rounded-full bg-gray-600 text-gray-200">
                      {tag}
                    </span>
                  ))}
                  <button className="text-blue-400 hover:text-blue-300 text-xs">+ Add Tag</button>
                </div>
              </div>
            </div>
          </div>
          
          <div className="bg-gray-700 rounded-lg p-4">
            <h5 className="text-sm font-medium text-gray-300 mb-3">Quick Actions</h5>
            <div className="space-y-2">
              <button className="w-full flex items-center space-x-2 px-3 py-2 text-sm bg-green-600 hover:bg-green-700 text-white rounded-lg">
                <CheckCircle2 className="w-4 h-4" />
                <span>Mark as Resolved</span>
              </button>
              <button className="w-full flex items-center space-x-2 px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                <MessageSquare className="w-4 h-4" />
                <span>Send Follow-up Email</span>
              </button>
              <button className="w-full flex items-center space-x-2 px-3 py-2 text-sm border border-gray-600 hover:bg-gray-600 text-white rounded-lg">
                <Star className="w-4 h-4" />
                <span>Add to Favorites</span>
              </button>
            </div>
          </div>
          
          <div className="bg-gray-700 rounded-lg p-4">
            <h5 className="text-sm font-medium text-gray-300 mb-3">Activity Log</h5>
            <div className="space-y-3">
              <div className="flex items-start">
                <div className="w-2 h-2 mt-1.5 rounded-full bg-blue-500 mr-2"></div>
                <div>
                  <p className="text-xs text-gray-300">Feedback created</p>
                  <p className="text-xs text-gray-500">
                    {new Date(feedback.date).toLocaleString()} • System
                  </p>
                </div>
              </div>
              
              {feedback.assignedTo && (
                <div className="flex items-start">
                  <div className="w-2 h-2 mt-1.5 rounded-full bg-yellow-500 mr-2"></div>
                  <div>
                    <p className="text-xs text-gray-300">
                      Assigned to <span className="font-medium">{feedback.assignedTo}</span>
                    </p>
                    <p className="text-xs text-gray-500">
                      {new Date().toLocaleString()} • You
                    </p>
                  </div>
                </div>
              )}
              
              {feedback.response && (
                <div className="flex items-start">
                  <div className="w-2 h-2 mt-1.5 rounded-full bg-green-500 mr-2"></div>
                  <div>
                    <p className="text-xs text-gray-300">Response sent to guest</p>
                    <p className="text-xs text-gray-500">
                      {feedback.responseDate && new Date(feedback.responseDate).toLocaleString()} • You
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
      
      <div className="mt-6 flex justify-between pt-4 border-t border-gray-700">
        <button 
          className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg"
          onClick={onClose}
        >
          Back to List
        </button>
        <div className="flex space-x-3">
          <button className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg">
            Print
          </button>
          <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
            Save Changes
          </button>
        </div>
      </div>
    </div>
  );

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="2xl">
      <div className="flex flex-col h-[80vh]">
        <div className="flex items-center justify-between border-b border-gray-700 pb-4 mb-6">
          <h2 className="text-xl font-semibold">Guest Feedback</h2>
          <button 
            onClick={onClose}
            className="text-gray-400 hover:text-white"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        
        {selectedFeedback ? (
          <FeedbackDetail 
            feedback={selectedFeedback} 
            onClose={() => setSelectedFeedback(null)} 
          />
        ) : (
          <>
            <div className="flex justify-between items-center mb-6">
              <div className="flex space-x-2">
                <div className="relative">
                  <input
                    type="text"
                    placeholder="Search feedback..."
                    className="w-64 bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                </div>
                
                <div className="relative">
                  <select className="appearance-none bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 pr-8 text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option>All Types</option>
                    <option>Complaints</option>
                    <option>Suggestions</option>
                    <option>Compliments</option>
                    <option>General</option>
                  </select>
                  <ChevronDown className="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                </div>
                
                <div className="relative">
                  <select className="appearance-none bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 pr-8 text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option>All Status</option>
                    <option>New</option>
                    <option>In Progress</option>
                    <option>Resolved</option>
                    <option>Archived</option>
                  </select>
                  <ChevronDown className="absolute right-3 top-2.5 h-4 w-4 text-gray-400 pointer-events-none" />
                </div>
                
                <button className="flex items-center space-x-1 px-3 py-2 border border-gray-600 rounded-lg text-gray-300 hover:bg-gray-700">
                  <Filter className="w-4 h-4" />
                  <span className="text-sm">Filter</span>
                </button>
              </div>
              
              <button 
                className="flex items-center space-x-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
                onClick={() => setActiveTab('new')}
              >
                <Plus className="w-4 h-4" />
                <span>New Feedback</span>
              </button>
            </div>
            
            <div className="flex-1 overflow-y-auto">
              {activeTab === 'list' ? (
                <div className="space-y-3">
                  {feedbackList.map((feedback) => (
                    <FeedbackItem 
                      key={feedback.id}
                      feedback={feedback}
                      onClick={() => setSelectedFeedback(feedback)}
                    />
                  ))}
                </div>
              ) : (
                <div className="bg-gray-800 p-6 rounded-lg border border-gray-700">
                  <h3 className="text-lg font-medium mb-6">New Guest Feedback</h3>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Guest Name</label>
                      <input 
                        type="text" 
                        className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter guest name"
                      />
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Email</label>
                      <input 
                        type="email" 
                        className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="guest@example.com"
                      />
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Stay Date</label>
                      <input 
                        type="date" 
                        className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Feedback Type</label>
                      <select className="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option>Select type</option>
                        <option>Complaint</option>
                        <option>Suggestion</option>
                        <option>Compliment</option>
                        <option>General Feedback</option>
                      </select>
                    </div>
                    
                    <div className="md:col-span-2">
                      <label className="block text-sm font-medium text-gray-300 mb-1">Rating</label>
                      <div className="flex space-x-1">
                        {[1, 2, 3, 4, 5].map((star) => (
                          <button 
                            key={star}
                            className="p-1"
                            onClick={(e) => {
                              e.preventDefault();
                              // Handle rating selection
                            }}
                          >
                            <Star className={`w-6 h-6 ${star <= 3 ? 'text-yellow-400' : 'text-gray-400'} fill-current`} />
                          </button>
                        ))}
                      </div>
                    </div>
                    
                    <div className="md:col-span-2">
                      <label className="block text-sm font-medium text-gray-300 mb-1">Message</label>
                      <textarea 
                        className="w-full h-32 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter feedback details..."
                      ></textarea>
                    </div>
                    
                    <div className="md:col-span-2">
                      <label className="block text-sm font-medium text-gray-300 mb-1">Attachments</label>
                      <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-600 border-dashed rounded-lg">
                        <div className="space-y-1 text-center">
                          <svg
                            className="mx-auto h-12 w-12 text-gray-400"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 48 48"
                            aria-hidden="true"
                          >
                            <path
                              d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                              strokeWidth={2}
                              strokeLinecap="round"
                              strokeLinejoin="round"
                            />
                          </svg>
                          <div className="flex text-sm text-gray-400">
                            <label
                              htmlFor="file-upload"
                              className="relative cursor-pointer bg-gray-800 rounded-md font-medium text-blue-500 hover:text-blue-400 focus-within:outline-none"
                            >
                              <span>Upload files</span>
                              <input id="file-upload" name="file-upload" type="file" className="sr-only" />
                            </label>
                            <p className="pl-1">or drag and drop</p>
                          </div>
                          <p className="text-xs text-gray-500">PNG, JPG, GIF up to 10MB</p>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div className="mt-8 flex justify-end space-x-3">
                    <button 
                      className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg"
                      onClick={() => setActiveTab('list')}
                    >
                      Cancel
                    </button>
                    <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                      Submit Feedback
                    </button>
                  </div>
                </div>
              )}
            </div>
          </>
        )}
      </div>
    </Modal>
  );
}
