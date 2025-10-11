'use client';

import { useState } from 'react';
import Modal from '@/components/ui/Modal';
import { X, Star, Gift, Award, Users, Search, Plus, CreditCard, ChevronDown, MoreVertical, CheckCircle2, Bed, Utensils, Sparkles } from 'lucide-react';

type LoyaltyTier = 'bronze' | 'silver' | 'gold' | 'platinum';

import { LoyaltyProgramModalProps } from './types';

interface LoyaltyMember {
  id: string;
  name: string;
  email: string;
  phone: string;
  tier: LoyaltyTier;
  points: number;
  joinDate: string;
  lastStay: string;
  totalStays: number;
  totalNights: number;
  totalSpent: number;
}

export default function LoyaltyProgramModal({ isOpen, onClose }: LoyaltyProgramModalProps) {
  const [activeTab, setActiveTab] = useState<'members' | 'tiers' | 'rewards'>('members');
  const [selectedMember, setSelectedMember] = useState<LoyaltyMember | null>(null);
  
  // Sample data
  const members: LoyaltyMember[] = [
    {
      id: 'M1001',
      name: 'John Smith',
      email: 'john.smith@example.com',
      phone: '+1 (555) 123-4567',
      tier: 'gold',
      points: 12500,
      joinDate: '2022-03-15',
      lastStay: '2023-05-20',
      totalStays: 8,
      totalNights: 24,
      totalSpent: 5240.75
    },
    {
      id: 'M1002',
      name: 'Sarah Johnson',
      email: 'sarah.j@example.com',
      phone: '+1 (555) 987-6543',
      tier: 'platinum',
      points: 34250,
      joinDate: '2021-11-02',
      lastStay: '2023-06-10',
      totalStays: 15,
      totalNights: 42,
      totalSpent: 11280.30
    },
    {
      id: 'M1003',
      name: 'Michael Chen',
      email: 'michael.chen@example.com',
      phone: '+1 (555) 456-7890',
      tier: 'silver',
      points: 7500,
      joinDate: '2023-01-20',
      lastStay: '2023-04-15',
      totalStays: 3,
      totalNights: 9,
      totalSpent: 1980.50
    },
  ];

  const TierBadge = ({ tier }: { tier: LoyaltyTier }) => {
    const styles = {
      bronze: 'bg-amber-600 text-white',
      silver: 'bg-gray-300 text-gray-800',
      gold: 'bg-yellow-500 text-white',
      platinum: 'bg-purple-600 text-white'
    };
    
    const labels = {
      bronze: 'Bronze',
      silver: 'Silver',
      gold: 'Gold',
      platinum: 'Platinum'
    };
    
    return (
      <span className={`text-xs px-2 py-1 rounded-full ${styles[tier]}`}>
        {labels[tier]}
      </span>
    );
  };

  const MemberCard = ({ member }: { member: LoyaltyMember }) => (
    <div 
      className="p-4 border border-gray-700 rounded-lg hover:bg-gray-800 cursor-pointer transition-colors mb-2"
      onClick={() => setSelectedMember(member)}
    >
      <div className="flex justify-between items-start">
        <div>
          <div className="flex items-center space-x-2">
            <h4 className="font-medium">{member.name}</h4>
            <TierBadge tier={member.tier} />
          </div>
          <p className="text-sm text-gray-400">{member.email}</p>
          <p className="text-sm text-gray-400">{member.phone}</p>
        </div>
        <div className="text-right">
          <div className="text-lg font-semibold">{member.points.toLocaleString()}</div>
          <div className="text-xs text-gray-400">points</div>
        </div>
      </div>
    </div>
  );

  const MemberDetail = ({ member, onClose }: { member: LoyaltyMember; onClose: () => void }) => (
    <div className="bg-gray-800 p-6 rounded-lg">
      <div className="flex justify-between items-start mb-6">
        <div>
          <h3 className="text-lg font-medium">Member Details</h3>
          <p className="text-gray-400">ID: {member.id}</p>
        </div>
        <button 
          onClick={onClose}
          className="text-gray-400 hover:text-white"
        >
          <X className="w-5 h-5" />
        </button>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
          <h4 className="text-sm font-medium text-gray-400 mb-2">PERSONAL INFORMATION</h4>
          <div className="space-y-2">
            <p className="text-white">{member.name}</p>
            <p className="text-gray-300">{member.email}</p>
            <p className="text-gray-300">{member.phone}</p>
          </div>
        </div>
        
        <div>
          <h4 className="text-sm font-medium text-gray-400 mb-2">MEMBERSHIP</h4>
          <div className="flex items-center space-x-2 mb-2">
            <TierBadge tier={member.tier} />
            <span className="text-white">{member.points.toLocaleString()} points</span>
          </div>
          <p className="text-sm text-gray-300">Member since {new Date(member.joinDate).toLocaleDateString()}</p>
        </div>
        
        <div>
          <h4 className="text-sm font-medium text-gray-400 mb-2">STAY HISTORY</h4>
          <div className="grid grid-cols-3 gap-4">
            <div className="text-center">
              <div className="text-2xl font-bold">{member.totalStays}</div>
              <div className="text-xs text-gray-400">Stays</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold">{member.totalNights}</div>
              <div className="text-xs text-gray-400">Nights</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold">${member.totalSpent.toLocaleString()}</div>
              <div className="text-xs text-gray-400">Total Spent</div>
            </div>
          </div>
        </div>
        
        <div>
          <h4 className="text-sm font-medium text-gray-400 mb-2">LAST STAY</h4>
          <p className="text-white">{new Date(member.lastStay).toLocaleDateString()}</p>
          <p className="text-sm text-gray-400">{Math.floor((new Date().getTime() - new Date(member.lastStay).getTime()) / (1000 * 60 * 60 * 24))} days ago</p>
        </div>
      </div>
      
      <div className="mt-6">
        <h4 className="text-sm font-medium text-gray-400 mb-2">AVAILABLE REWARDS</h4>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="border border-gray-700 rounded-lg p-4">
            <div className="flex items-center space-x-2 mb-2">
              <Gift className="w-5 h-5 text-yellow-400" />
              <h5 className="font-medium">Free Night Stay</h5>
            </div>
            <p className="text-sm text-gray-400 mb-3">Redeem 25,000 points for a free night</p>
            <button 
              className="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm"
              disabled={member.points < 25000}
            >
              {member.points >= 25000 ? 'Redeem Now' : `${(25000 - member.points).toLocaleString()} more points needed`}
            </button>
          </div>
          
          <div className="border border-gray-700 rounded-lg p-4">
            <div className="flex items-center space-x-2 mb-2">
              <Award className="w-5 h-5 text-purple-400" />
              <h5 className="font-medium">Room Upgrade</h5>
            </div>
            <p className="text-sm text-gray-400 mb-3">15,000 points for a one-category upgrade</p>
            <button 
              className="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm"
              disabled={member.points < 15000}
            >
              {member.points >= 15000 ? 'Redeem Now' : `${(15000 - member.points).toLocaleString()} more points needed`}
            </button>
          </div>
          
          <div className="border border-gray-700 rounded-lg p-4">
            <div className="flex items-center space-x-2 mb-2">
              <CreditCard className="w-5 h-5 text-green-400" />
              <h5 className="font-medium">$100 Dining Credit</h5>
            </div>
            <p className="text-sm text-gray-400 mb-3">10,000 points for $100 credit at our restaurants</p>
            <button 
              className="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm"
              disabled={member.points < 10000}
            >
              {member.points >= 10000 ? 'Redeem Now' : `${(10000 - member.points).toLocaleString()} more points needed`}
            </button>
          </div>
        </div>
      </div>
      
      <div className="mt-6 flex justify-end space-x-3">
        <button 
          className="px-4 py-2 border border-gray-600 hover:bg-gray-700 text-white rounded-lg"
          onClick={onClose}
        >
          Back to List
        </button>
        <button className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
          Edit Member
        </button>
      </div>
    </div>
  );

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="2xl">
      <div className="flex flex-col h-[80vh]">
        <div className="flex items-center justify-between border-b border-gray-700 pb-4 mb-6">
          <h2 className="text-xl font-semibold">Loyalty Program</h2>
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
              activeTab === 'members'
                ? 'text-blue-400 border-b-2 border-blue-400'
                : 'text-gray-400 hover:text-white'
            }`}
            onClick={() => setActiveTab('members')}
          >
            <div className="flex items-center space-x-2">
              <Users className="w-4 h-4" />
              <span>Members</span>
            </div>
          </button>
          <button
            type="button"
            className={`px-6 py-3 font-medium ${
              activeTab === 'tiers'
                ? 'text-blue-400 border-b-2 border-blue-400'
                : 'text-gray-400 hover:text-white'
            }`}
            onClick={() => setActiveTab('tiers')}
          >
            <div className="flex items-center space-x-2">
              <Award className="w-4 h-4" />
              <span>Tier Benefits</span>
            </div>
          </button>
          <button
            type="button"
            className={`px-6 py-3 font-medium ${
              activeTab === 'rewards'
                ? 'text-blue-400 border-b-2 border-blue-400'
                : 'text-gray-400 hover:text-white'
            }`}
            onClick={() => setActiveTab('rewards')}
          >
            <div className="flex items-center space-x-2">
              <Gift className="w-4 h-4" />
              <span>Rewards</span>
            </div>
          </button>
        </div>
        
        <div className="flex-1 overflow-y-auto">
          {activeTab === 'members' ? (
            selectedMember ? (
              <MemberDetail 
                member={selectedMember} 
                onClose={() => setSelectedMember(null)} 
              />
            ) : (
              <div>
                <div className="flex justify-between items-center mb-6">
                  <div className="relative w-96">
                    <input
                      type="text"
                      placeholder="Search members..."
                      className="w-full bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                  </div>
                  <button className="flex items-center space-x-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <Plus className="w-4 h-4" />
                    <span>Add Member</span>
                  </button>
                </div>
                
                <div className="space-y-3">
                  {members.map((member) => (
                    <MemberCard 
                      key={member.id}
                      member={member}
                    />
                  ))}
                </div>
              </div>
            )
          ) : activeTab === 'tiers' ? (
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
              {/* Bronze Tier */}
              <div className="border border-amber-700 rounded-lg overflow-hidden">
                <div className="bg-amber-700 p-4 text-center">
                  <h3 className="text-lg font-medium text-white">Bronze</h3>
                  <p className="text-amber-100 text-sm">0 - 4,999 points</p>
                </div>
                <div className="p-4 bg-gray-800">
                  <ul className="space-y-2 text-sm">
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Earn 10 points per $1 spent</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Free Wi-Fi</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Late check-out upon request</span>
                    </li>
                    <li className="flex items-start text-gray-500">
                      <X className="w-4 h-4 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Room upgrade</span>
                    </li>
                    <li className="flex items-start text-gray-500">
                      <X className="w-4 h-4 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Welcome amenity</span>
                    </li>
                  </ul>
                </div>
              </div>
              
              {/* Silver Tier */}
              <div className="border border-gray-500 rounded-lg overflow-hidden">
                <div className="bg-gray-500 p-4 text-center">
                  <h3 className="text-lg font-medium text-white">Silver</h3>
                  <p className="text-gray-100 text-sm">5,000 - 14,999 points</p>
                </div>
                <div className="p-4 bg-gray-800">
                  <ul className="space-y-2 text-sm">
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Earn 12 points per $1 spent</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Free Wi-Fi</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Late check-out until 2 PM</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Complimentary room upgrade when available</span>
                    </li>
                    <li className="flex items-start text-gray-500">
                      <X className="w-4 h-4 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Welcome amenity</span>
                    </li>
                  </ul>
                </div>
              </div>
              
              {/* Gold Tier */}
              <div className="border border-yellow-600 rounded-lg overflow-hidden">
                <div className="bg-yellow-600 p-4 text-center">
                  <h3 className="text-lg font-medium text-white">Gold</h3>
                  <p className="text-yellow-100 text-sm">15,000 - 29,999 points</p>
                </div>
                <div className="p-4 bg-gray-800">
                  <ul className="space-y-2 text-sm">
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Earn 15 points per $1 spent</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Premium Wi-Fi</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Late check-out until 3 PM</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Room upgrade upon arrival</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Welcome amenity</span>
                    </li>
                  </ul>
                </div>
              </div>
              
              {/* Platinum Tier */}
              <div className="border border-purple-700 rounded-lg overflow-hidden">
                <div className="bg-purple-700 p-4 text-center">
                  <h3 className="text-lg font-medium text-white">Platinum</h3>
                  <p className="text-purple-100 text-sm">30,000+ points</p>
                </div>
                <div className="p-4 bg-gray-800">
                  <ul className="space-y-2 text-sm">
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Earn 20 points per $1 spent</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Premium Wi-Fi & streaming</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>4 PM late check-out</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Best available room at check-in</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Premium welcome amenity & bottle of wine</span>
                    </li>
                    <li className="flex items-start">
                      <CheckCircle2 className="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                      <span>Dedicated concierge service</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          ) : (
            // Rewards Tab
            <div className="space-y-6">
              <div className="bg-gray-800 p-6 rounded-lg border border-gray-700">
                <h3 className="text-lg font-medium mb-4">Featured Rewards</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div className="border border-gray-700 rounded-lg p-4 hover:bg-gray-750 transition-colors">
                    <div className="flex items-center space-x-2 mb-3">
                      <Bed className="w-5 h-5 text-blue-400" />
                      <h4 className="font-medium">Free Night Stay</h4>
                    </div>
                    <p className="text-sm text-gray-400 mb-4">Redeem points for a complimentary night at any of our properties.</p>
                    <div className="flex justify-between items-center">
                      <span className="text-yellow-400 font-medium">25,000 points</span>
                      <button className="text-blue-400 hover:text-blue-300 text-sm font-medium">
                        Learn More
                      </button>
                    </div>
                  </div>
                  
                  <div className="border border-gray-700 rounded-lg p-4 hover:bg-gray-750 transition-colors">
                    <div className="flex items-center space-x-2 mb-3">
                      <Utensils className="w-5 h-5 text-green-400" />
                      <h4 className="font-medium">Dining Credit</h4>
                    </div>
                    <p className="text-sm text-gray-400 mb-4">Enjoy a meal on us with dining credits at our restaurants.</p>
                    <div className="flex justify-between items-center">
                      <span className="text-yellow-400 font-medium">10,000 points</span>
                      <button className="text-blue-400 hover:text-blue-300 text-sm font-medium">
                        Learn More
                      </button>
                    </div>
                  </div>
                  
                  <div className="border border-gray-700 rounded-lg p-4 hover:bg-gray-750 transition-colors">
                    <div className="flex items-center space-x-2 mb-3">
                      <Sparkles className="w-5 h-5 text-purple-400" />
                      <h4 className="font-medium">Spa Package</h4>
                    </div>
                    <p className="text-sm text-gray-400 mb-4">Pamper yourself with a relaxing spa experience.</p>
                    <div className="flex justify-between items-center">
                      <span className="text-yellow-400 font-medium">15,000 points</span>
                      <button className="text-blue-400 hover:text-blue-300 text-sm font-medium">
                        Learn More
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="bg-gray-800 p-6 rounded-lg border border-gray-700">
                <h3 className="text-lg font-medium mb-4">All Rewards</h3>
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-700">
                    <thead>
                      <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Reward</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Description</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Points</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tier</th>
                        <th className="relative px-6 py-3"></th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-700">
                      {[
                        { name: 'Free Night Stay', description: 'One night stay at any standard room', points: '25,000', tier: 'All Tiers' },
                        { name: 'Room Upgrade', description: 'One-category room upgrade on your next stay', points: '15,000', tier: 'Silver+' },
                        { name: '$100 Dining Credit', description: 'Credit at any of our hotel restaurants', points: '10,000', tier: 'All Tiers' },
                        { name: 'Spa Package', description: '60-minute massage for one person', points: '15,000', tier: 'Gold+' },
                        { name: 'Airport Transfer', description: 'Complimentary one-way airport transfer', points: '8,000', tier: 'Silver+' },
                        { name: 'Late Check-out', description: 'Guaranteed 4 PM check-out', points: '5,000', tier: 'All Tiers' },
                        { name: 'Welcome Amenity', description: 'Special welcome gift upon arrival', points: '3,000', tier: 'All Tiers' },
                      ].map((reward, index) => (
                        <tr key={index} className="hover:bg-gray-750">
                          <td className="px-6 py-4 whitespace-nowrap">
                            <div className="text-sm font-medium text-white">{reward.name}</div>
                          </td>
                          <td className="px-6 py-4">
                            <div className="text-sm text-gray-400">{reward.description}</div>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <div className="text-sm text-yellow-400">{reward.points}</div>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-700 text-gray-300">
                              {reward.tier}
                            </span>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button className="text-blue-400 hover:text-blue-300">Redeem</button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </Modal>
  );
}
