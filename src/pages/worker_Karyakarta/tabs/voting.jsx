import React from 'react';
import KarkartaEvmVotingSystem from '../components/KarkartaEvmVotingSystem';

export default function VotingTab({ user, isMr, isAdmin = false }) {
  return <KarkartaEvmVotingSystem user={user} isMr={isMr} isAdmin={isAdmin} />;
}
