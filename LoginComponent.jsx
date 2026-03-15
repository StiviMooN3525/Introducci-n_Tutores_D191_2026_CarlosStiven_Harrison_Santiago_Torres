import React, { useState } from 'react';
import { motion } from 'framer-motion';

const LoginComponent = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState('estudiante');
  const [error, setError] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    // Aquí iría la lógica de autenticación
    console.log('Login attempt:', { email, password, role });
  };

  const roles = [
    { id: 'estudiante', label: 'Estudiante', icon: '👨‍🎓' },
    { id: 'profesor', label: 'Profesor', icon: '👨‍🏫' },
    { id: 'administrador', label: 'Administrador', icon: '👔' }
  ];

  return (
    <div className="min-h-screen bg-gradient-radial from-[#0a0a0a] via-[#121212] to-[#0a0a0a] flex items-center justify-center p-4 font-['Inter',sans-serif]">
      {/* Fondo con sombras profundas */}
      <div className="absolute inset-0 bg-gradient-radial from-transparent via-[#1a1a1a]/20 to-[#0a0a0a]/80"></div>

      <motion.div
        initial={{ opacity: 0, y: 50 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.8, ease: "easeOut" }}
        className="relative z-10 w-full max-w-md"
      >
        {/* Card de Login con Glassmorphism */}
        <div className="backdrop-blur-xl bg-[#121212]/80 border border-white/10 rounded-3xl p-8 shadow-2xl shadow-black/50">
          {/* Header */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.2, duration: 0.6 }}
            className="text-center mb-8"
          >
            <h1 className="text-3xl font-bold text-white mb-2 tracking-tight">
              Acceder al Sistema
            </h1>
            <p className="text-gray-400 text-sm">
              Selecciona tu rol e ingresa tus credenciales
            </p>
          </motion.div>

          {/* Selector de Rol */}
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: 0.4, duration: 0.6 }}
            className="mb-6"
          >
            <label className="block text-sm font-medium text-gray-300 mb-3">
              Rol
            </label>
            <div className="grid grid-cols-3 gap-2">
              {roles.map((r) => (
                <button
                  key={r.id}
                  onClick={() => setRole(r.id)}
                  className={`p-3 rounded-xl border transition-all duration-300 ${
                    role === r.id
                      ? 'border-cyan-400 bg-cyan-400/20 text-cyan-300 shadow-lg shadow-cyan-400/25'
                      : 'border-gray-600 bg-gray-800/50 text-gray-400 hover:border-gray-500 hover:bg-gray-700/50'
                  }`}
                >
                  <div className="text-2xl mb-1">{r.icon}</div>
                  <div className="text-xs font-medium">{r.label}</div>
                </button>
              ))}
            </div>
          </motion.div>

          {/* Formulario */}
          <motion.form
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.6, duration: 0.6 }}
            onSubmit={handleSubmit}
            className="space-y-6"
          >
            {/* Error Message */}
            {error && (
              <motion.div
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                className="p-3 bg-red-900/50 border border-red-500/50 rounded-xl text-red-300 text-sm"
              >
                {error}
              </motion.div>
            )}

            {/* Email Input */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                Correo Electrónico
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-4 py-3 bg-gray-900/50 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20 transition-all duration-300"
                placeholder="tu@email.com"
                required
              />
            </div>

            {/* Password Input */}
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">
                Contraseña
              </label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-4 py-3 bg-gray-900/50 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/20 transition-all duration-300"
                placeholder="••••••••"
                required
              />
            </div>

            {/* Botón de Acción */}
            <motion.button
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              type="submit"
              className="w-full py-3 px-6 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-semibold rounded-xl shadow-lg shadow-cyan-500/25 hover:shadow-cyan-500/40 transition-all duration-300 relative overflow-hidden"
            >
              <span className="relative z-10">Iniciar Sesión</span>
              <div className="absolute inset-0 bg-gradient-to-r from-cyan-400 to-blue-500 opacity-0 hover:opacity-100 transition-opacity duration-300"></div>
            </motion.button>
          </motion.form>

          {/* Footer */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.8, duration: 0.6 }}
            className="mt-6 text-center"
          >
            <p className="text-gray-500 text-sm">
              ¿No tienes cuenta?{' '}
              <a href="#" className="text-cyan-400 hover:text-cyan-300 transition-colors duration-300">
                Regístrate aquí
              </a>
            </p>
          </motion.div>
        </div>
      </motion.div>
    </div>
  );
};

export default LoginComponent;