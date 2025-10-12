'use client';

import { useState } from 'react';
import Modal from '@/components/ui/Modal';
import { X, Clock, AlertCircle, CheckCircle2 } from 'lucide-react';

type TaskStatus = 'pending' | 'in-progress' | 'completed' | 'issue';

interface Task {
  id: string;
  room: string;
  type: 'cleaning' | 'maintenance' | 'inspection' | 'other';
  status: TaskStatus;
  priority: 'low' | 'medium' | 'high';
  assignedTo: string;
  dueDate: string;
  notes: string;
  createdAt: string;
}

export default function HousekeepingModal({ 
  isOpen, 
  onClose 
}: { 
  isOpen: boolean; 
  onClose: () => void 
}) {
  const [activeTab, setActiveTab] = useState<'tasks' | 'schedule'>('tasks');
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);
  
  // Sample tasks data
  const [tasks, setTasks] = useState<Task[]>([
    {
      id: '1',
      room: '201',
      type: 'cleaning',
      status: 'pending',
      priority: 'high',
      assignedTo: 'Staff 1',
      dueDate: '2023-06-15T10:00:00',
      notes: 'Guest requested extra towels',
      createdAt: '2023-06-14T09:00:00'
    },
    {
      id: '2',
      room: '305',
      type: 'maintenance',
      status: 'in-progress',
      priority: 'medium',
      assignedTo: 'Staff 2',
      dueDate: '2023-06-15T14:00:00',
      notes: 'AC not working properly',
      createdAt: '2023-06-14T11:30:00'
    },
    {
      id: '3',
      room: '112',
      type: 'inspection',
      status: 'pending',
      priority: 'low',
      assignedTo: 'Staff 3',
      dueDate: '2023-06-16T09:00:00',
      notes: 'Routine inspection',
      createdAt: '2023-06-14T15:45:00'
    }
  ]);

  const updateTaskStatus = (taskId: string, newStatus: TaskStatus) => {
    setTasks(tasks.map(task => 
      task.id === taskId ? { ...task, status: newStatus } : task
    ));
  };

  const PriorityBadge = ({ priority }: { priority: 'low' | 'medium' | 'high' }) => {
    const styles = {
      low: 'bg-green-100 text-green-800',
      medium: 'bg-yellow-100 text-yellow-800',
      high: 'bg-red-100 text-red-800'
    };
    
    return (
      <span className={`text-xs px-2 py-1 rounded-full ${styles[priority]}`}>
        {priority}
      </span>
    );
  };

  const StatusBadge = ({ status }: { status: TaskStatus }) => {
    const styles = {
      pending: 'bg-yellow-100 text-yellow-800',
      'in-progress': 'bg-blue-100 text-blue-800',
      completed: 'bg-green-100 text-green-800',
      issue: 'bg-red-100 text-red-800'
    };
    
    const icons = {
      pending: <Clock className="w-3 h-3 mr-1" />,
      'in-progress': <Clock className="w-3 h-3 mr-1" />,
      completed: <CheckCircle2 className="w-3 h-3 mr-1" />,
      issue: <AlertCircle className="w-3 h-3 mr-1" />
    };
    
    return (
      <span className={`inline-flex items-center text-xs px-2 py-1 rounded-full ${styles[status]}`}>
        {icons[status]}
        {status.replace('-', ' ')}
      </span>
    );
  };

  const TaskItem = ({ task, onClick }: { task: Task; onClick: () => void }) => (
    <div 
      className="p-4 border border-gray-700 rounded-lg hover:bg-gray-800 cursor-pointer transition-colors mb-2"
      onClick={onClick}
    >
      <div className="flex justify-between items-center">
        <div>
          <h4 className="font-medium">Room {task.room} - {task.type}</h4>
          <p className="text-sm text-gray-400">{task.assignedTo}</p>
        </div>
        <div className="flex space-x-2">
          <PriorityBadge priority={task.priority} />
          <StatusBadge status={task.status} />
        </div>
      </div>
    </div>
  );

  const TaskDetail = ({ task, onClose }: { task: Task; onClose: () => void }) => (
    <div className="bg-gray-800 p-4 rounded-lg">
      <div className="flex justify-between items-start mb-4">
        <h3 className="text-lg font-medium">Room {task.room} - {task.type}</h3>
        <button 
          onClick={onClose}
          className="text-gray-400 hover:text-white"
        >
          <X className="w-5 h-5" />
        </button>
      </div>
      
      <div className="grid grid-cols-2 gap-4 mb-4">
        <div>
          <p className="text-sm text-gray-400">Status</p>
          <StatusBadge status={task.status} />
        </div>
        <div>
          <p className="text-sm text-gray-400">Priority</p>
          <PriorityBadge priority={task.priority} />
        </div>
        <div>
          <p className="text-sm text-gray-400">Assigned To</p>
          <p>{task.assignedTo}</p>
        </div>
        <div>
          <p className="text-sm text-gray-400">Due Date</p>
          <p>{new Date(task.dueDate).toLocaleString()}</p>
        </div>
      </div>
      
      <div className="mb-4">
        <p className="text-sm text-gray-400 mb-1">Notes</p>
        <p className="text-sm">{task.notes}</p>
      </div>
      
      <div className="flex space-x-2">
        <button 
          className="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
          onClick={() => {
            updateTaskStatus(task.id, 'completed');
            onClose();
          }}
        >
          Mark as Complete
        </button>
        <button 
          className="px-3 py-1.5 bg-gray-700 text-white rounded hover:bg-gray-600 text-sm"
          onClick={onClose}
        >
          Close
        </button>
      </div>
    </div>
  );

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="2xl">
      <div className="flex flex-col h-[80vh]">
        <div className="flex items-center justify-between border-b border-gray-700 pb-4 mb-6">
          <h2 className="text-xl font-semibold">Housekeeping</h2>
          <button 
            onClick={onClose}
            className="text-gray-400 hover:text-white"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
        
        <div className="flex border-b border-gray-700 mb-6">
          <button
            type="button"
            className={`px-6 py-3 font-medium ${
              activeTab === 'tasks'
                ? 'text-blue-400 border-b-2 border-blue-400'
                : 'text-gray-400 hover:text-white'
            }`}
            onClick={() => setActiveTab('tasks')}
          >
            Tasks
          </button>
          <button
            type="button"
            className={`px-6 py-3 font-medium ${
              activeTab === 'schedule'
                ? 'text-blue-400 border-b-2 border-blue-400'
                : 'text-gray-400 hover:text-white'
            }`}
            onClick={() => setActiveTab('schedule')}
          >
            Schedule
          </button>
        </div>
        
        <div className="flex-1 overflow-y-auto">
          {selectedTask ? (
            <TaskDetail 
              task={selectedTask} 
              onClose={() => setSelectedTask(null)} 
            />
          ) : (
            <>
              {activeTab === 'tasks' && (
                <div className="space-y-3">
                  {tasks.map((task) => (
                    <TaskItem 
                      key={task.id} 
                      task={task} 
                      onClick={() => setSelectedTask(task)}
                    />
                  ))}
                </div>
              )}
              
              {activeTab === 'schedule' && (
                <div className="text-center py-8 text-gray-400">
                  <p>Schedule view coming soon</p>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </Modal>
  );
}
