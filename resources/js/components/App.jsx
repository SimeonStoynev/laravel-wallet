import React, { useState } from 'react';
import axios from 'axios';

function csrf() {
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

const LoginForm = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const onSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      csrf();
      await axios.post('/login', {
        email,
        password,
      });
      window.location = '/dashboard';
    } catch (err) {
      setError('Invalid credentials.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={onSubmit} className="mt-3">
      {error && <div className="alert alert-danger" role="alert">{error}</div>}
      <div className="form-group">
        <label htmlFor="loginEmail">Email address</label>
        <input
          id="loginEmail"
          type="email"
          className="form-control"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
      </div>
      <div className="form-group">
        <label htmlFor="loginPassword">Password</label>
        <input
          id="loginPassword"
          type="password"
          className="form-control"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
      </div>
      <button type="submit" className="btn btn-primary btn-block" disabled={loading}>
        {loading ? 'Signing In…' : 'Login'}
      </button>
    </form>
  );
};

const RegisterForm = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [loading, setLoading] = useState(false);

  const onSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setNotice('');
    setLoading(true);
    try {
      csrf();
      await axios.post('/register', {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
      });
      // On success, backend logs that an email was sent. We redirect to dashboard.
      setNotice('Registration successful. Redirecting…');
      window.location = '/dashboard';
    } catch (err) {
      if (err.response && err.response.data && err.response.data.errors) {
        const firstKey = Object.keys(err.response.data.errors)[0];
        setError(err.response.data.errors[firstKey][0]);
      } else {
        setError('Registration failed. Please check your input.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={onSubmit} className="mt-3">
      {notice && <div className="alert alert-success" role="alert">{notice}</div>}
      {error && <div className="alert alert-danger" role="alert">{error}</div>}
      <div className="form-group">
        <label htmlFor="registerName">Name</label>
        <input
          id="registerName"
          type="text"
          className="form-control"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
        />
      </div>
      <div className="form-group">
        <label htmlFor="registerEmail">Email address</label>
        <input
          id="registerEmail"
          type="email"
          className="form-control"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
      </div>
      <div className="form-group">
        <label htmlFor="registerPassword">Password</label>
        <input
          id="registerPassword"
          type="password"
          className="form-control"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
      </div>
      <div className="form-group">
        <label htmlFor="registerPasswordConfirmation">Confirm Password</label>
        <input
          id="registerPasswordConfirmation"
          type="password"
          className="form-control"
          value={passwordConfirmation}
          onChange={(e) => setPasswordConfirmation(e.target.value)}
          required
        />
      </div>
      <button type="submit" className="btn btn-success btn-block" disabled={loading}>
        {loading ? 'Creating Account…' : 'Register'}
      </button>
    </form>
  );
};

export default function App() {
  const [activeTab, setActiveTab] = useState('login');

  return (
    <div className="container py-5">
      <div className="row justify-content-center">
        <div className="col-md-8 col-lg-6">
          <div className="card shadow-sm">
            <div className="card-header bg-white">
              <nav className="navbar navbar-expand navbar-light">
                <span className="navbar-brand font-weight-bold">Wallet</span>
                <ul className="navbar-nav ml-auto">
                  <li className={`nav-item ${activeTab === 'login' ? 'active' : ''}`}>
                    <button className="btn btn-link nav-link" onClick={() => setActiveTab('login')}>Login</button>
                  </li>
                  <li className={`nav-item ${activeTab === 'register' ? 'active' : ''}`}>
                    <button className="btn btn-link nav-link" onClick={() => setActiveTab('register')}>Register</button>
                  </li>
                </ul>
              </nav>
            </div>
            <div className="card-body">
              {activeTab === 'login' ? <LoginForm /> : <RegisterForm />}
            </div>
            <div className="card-footer text-center text-muted small">
              © {new Date().getFullYear()} Wallet App
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
