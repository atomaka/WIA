class Blog < ActiveRecord::Base
  attr_accessible :title, :body, :release
  validates :title, :presence => true, :length => { :minimum => 3 }
  validates :body, :presence => true

  def self.released
    Blog.where("DATE(release) <= DATE(?)", Time.now).order("release DESC")
  end

  def self.get(id = nil)
    return false if nil

    blog = Blog.find(id)

    return false if blog.release == nil

    return blog
  end
end
